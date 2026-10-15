<?php
/**
 * Copyright (c) 2026 MageSetu. All rights reserved.
 *
 * @package    MageSetu_SemanticSearch
 * @author     MageSetu
 * @copyright  Copyright (c) 2026 MageSetu
 * @license    https://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link       https://github.com/MageSetu/module-semantic-search
 */

declare(strict_types=1);

namespace MageSetu\SemanticSearch\Model\Vector\Storage;

use MageSetu\SemanticSearch\Model\Vector\Storage\AbstractStorage;
use MageSetu\SemanticSearch\Api\EnvironmentResolverInterface;
use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterfaceFactory;
use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Model\Search\OpenSearchClientProvider;
use OpenSearch\Client;
use MageSetu\SemanticSearch\Exception\VectorStorageException;

/**
 * OpenSearch implementation of VectorStorageInterface.
 *
 * Uses the opensearch-php client under the hood. Index name and the kNN
 * engine are injectable so you can override them in di.xml
 */
class OpenSearch extends AbstractStorage
{
    protected const CODE = 'opensearch';

    /**
     * Local open search client
     *
     * @var Client
     */
    protected ?Client $client = null;

    /**
     * @param EnvironmentResolverInterface $environmentResolver
     * @param VectorDocumentInterfaceFactory $documentFactory Factory for VectorDocumentInterface instances.
     * @param Logger $logger
     * @param OpenSearchClientProvider $clientProvider - Injected OpenSearch PHP client.
     * @param string $knnEngine - kNN engine: 'nmslib' (default) or 'faiss' or 'lucene'.
     * @param string $spaceType - Distance metric: 'cosinesimil', 'l2', 'innerproduct'.
     * @param int $efConstruction - HNSW ef_construction tuning parameter.
     * @param int $m - HNSW M tuning parameter.
     */
    public function __construct(
        EnvironmentResolverInterface $environmentResolver,
        VectorDocumentInterfaceFactory $documentFactory,
        Logger $logger,
        protected readonly OpenSearchClientProvider $clientProvider,
        protected readonly string $knnEngine = 'nmslib',
        protected readonly string $spaceType = 'cosinesimil',
        protected readonly int $efConstruction = 128,
        protected readonly int $m = 16
    ) {
        parent::__construct($environmentResolver, $documentFactory, $logger);
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * @inheritDoc
     */
    public function needsRefreshForBulkOperations(): bool
    {
        return true;
    }

    /**
     * Get OpenSearch client
     *
     * @return Client
     */
    protected function getClient(): Client
    {
        if (!$this->client) {
            $this->client = $this->clientProvider->getClient();
        }

        return $this->client;
    }

    // -------------------------------------------------------------------------
    // Index lifecycle
    // -------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function createCollectionIfNotExist(int $storeId, int $dimensions, array $settings = []): void
    {
        if ($this->collectionExists($storeId)) {
            return;
        }

        $index = $this->getCollectionName($storeId);

        $body = array_merge_recursive(
            [
                'settings' => [
                    'index' => [
                        'knn' => true,
                        'knn.algo_param.ef_search' => 100,
                        'number_of_shards' => 1,
                        'number_of_replicas' => 1,
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'vector' => [
                            'type' => 'knn_vector',
                            'dimension' => $dimensions,
                            'method' => [
                                'name' => 'hnsw',
                                'engine' => $this->knnEngine,
                                'space_type' => $this->spaceType,
                                'parameters' => [
                                    'ef_construction' => $this->efConstruction,
                                    'm' => $this->m,
                                ],
                            ],
                        ],
                        'metadata' => [
                            'type' => 'object',
                        ],
                    ],
                ],
            ],
            $settings
        );

        try {
            $this->getClient()->indices()->create([
                'index' => $index,
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            $this->logger->error("[OpenSearch] Failed to create vector index.", [
                'index' => $index,
                'exception' => $e
            ]);

            throw new VectorStorageException('Failed to create vector index.');
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteCollection(int $storeId): void
    {
        if (!$this->collectionExists($storeId)) {
            return;
        }

        $index = $this->getCollectionName($storeId);

        try {
            $this->getClient()->indices()->delete(['index' => $index]);
        } catch (\Exception $e) {
            $this->logger->error("[OpenSearch] Failed to delete vector index.", [
                'index' => $index,
                'exception' => $e
            ]);

            throw new VectorStorageException('Failed to delete vector index.');
        }
    }

    /**
     * @inheritDoc
     */
    public function collectionExists($storeId): bool
    {
        $index = $this->getCollectionName($storeId);

        try {
            return (bool) $this->getClient()->indices()->exists(['index' => $index]);
        } catch (\Exception $e) {
            $this->logger->error("[OpenSearch] Failed to check vector index existence.", [
                'index' => $index,
                'exception' => $e
            ]);

            throw new VectorStorageException('Failed to check vector index existence.');
        }
    }

    // -------------------------------------------------------------------------
    // Document writes
    // -------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function saveDocument(VectorDocumentInterface $document, int $storeId): void
    {
        $index = $this->getCollectionName($storeId);

        try {
            $this->getClient()->index([
                'index' => $index,
                'id' => $document->getId(),
                'body' => $this->documentToBody($document),
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('[OpenSearch] Failed to save vector document id=%s', $document->getId()),
                [
                    'index' => $index,
                    'exception' => $e
                ]
            );

            throw new VectorStorageException(
                sprintf('Failed to save vector document id=%s', $document->getId())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function saveDocuments(array $documents, int $storeId): void
    {
        if (empty($documents)) {
            return;
        }

        foreach ($this->chunkBySize($documents) as $chunk) {
            $this->handleMassSaveDocuments($chunk, $storeId);
        }
    }

    /**
     * Chunk the documents by size
     *
     * @param array $documents
     * @return array
     */
    protected function chunkBySize(array $documents): array
    {
        $chunks = [];
        $current = [];
        $currentBytes = 0;

        foreach ($documents as $doc) {
            $docBytes = strlen(json_encode($doc));

            if (
                $current && ($currentBytes + $docBytes > self::MAX_BULK_BYTES
                    || count($current) >= self::MAX_BULK_DOCS)
            ) {
                $chunks[] = $current;
                $current = [];
                $currentBytes = 0;
            }

            $current[] = $doc;
            $currentBytes += $docBytes;
        }

        if ($current) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * Handle mass documents save to OpenSearch
     *
     * @param VectorDocumentInterface[] $documents
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    protected function handleMassSaveDocuments(array $documents, int $storeId): void
    {
        $index = $this->getCollectionName($storeId);
        $params = ['refresh' => false, 'body' => []];

        foreach ($documents as $document) {
            // Action header
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_id' => $document->getId(),
                ],
            ];
            // Document body
            $params['body'][] = $this->documentToBody($document);
        }

        try {
            $response = $this->getClient()->bulk($params);
        } catch (\Exception $e) {
            $this->logger->error(
                "[OpenSearch] Failed to bulk-save vector documents",
                [
                    'index' => $index,
                    'exception' => $e
                ]
            );

            throw new VectorStorageException("Failed to bulk-save vector documents");
        }

        // OpenSearch bulk responses can report per-item errors without throwing.
        if (!empty($response['errors'])) {
            $this->logBulkErrors($response['items'] ?? []);
            throw new VectorStorageException("Failed to bulk-save vector documents due to errors in bulk items.");
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDocument(int|string $id, int $storeId): void
    {
        $index = $this->getCollectionName($storeId);

        try {
            $this->getClient()->delete([
                'index' => $index,
                'id' => (string) $id,
                'client' => ['ignore' => [404]], // idempotent
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('[OpenSearch] Failed to delete vector document id=%s', $id),
                [
                    'index' => $index,
                    'error' => $e->getMessage()
                ]
            );

            throw new VectorStorageException(
                sprintf("Failed to delete vector document id=%s", $id)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDocuments(array $ids, int $storeId): void
    {
        if (empty($ids)) {
            return;
        }

        $index = $this->getCollectionName($storeId);

        $batches = array_chunk($ids, self::MAX_BULK_DELETE_DOCS);
        foreach ($batches as $batch) {
            $params = ['refresh' => false, 'body' => []];

            foreach ($batch as $id) {
                $params['body'][] = [
                    'delete' => [
                        '_index' => $index,
                        '_id' => (string) $id,
                    ],
                ];
            }

            try {
                $response = $this->getClient()->bulk($params);
            } catch (\Exception $e) {
                $this->logger->error(
                    "[OpenSearch] Failed to bulk-delete vector documents",
                    [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]
                );

                throw new VectorStorageException("Failed to bulk-delete vector documents");
            }

            // OpenSearch bulk responses can report per-item errors without throwing.
            if (!empty($response['errors'])) {
                $this->logBulkErrors($response['items'] ?? []);
                throw new VectorStorageException("Failed to bulk-delete vector documents due to errors in bulk items.");
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDocumentsByProductIds(array $ids, int $storeId): void
    {
        if (empty($ids)) {
            return;
        }

        $index = $this->getCollectionName($storeId);

        $batches = array_chunk($ids, self::MAX_BULK_DELETE_DOCS);
        foreach ($batches as $batch) {
            $params = [
                'index' => $index,
                'body' => [
                    'query' => [
                        'terms' => [
                            'metadata.product_id' => array_map('strval', $batch),
                        ],
                    ],
                ],
                'refresh' => false,
                'conflicts' => 'proceed', // don't fail on version conflicts from concurrent writes
            ];

            try {
                $response = $this->getClient()->deleteByQuery($params);
            } catch (\Exception $e) {
                $this->logger->error(
                    "[OpenSearch] Failed to delete vector documents by query.",
                    [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]
                );

                throw new VectorStorageException("Failed to delete vector documents by query");
            }

            // delete_by_query reports failures in a 'failures' array rather than an 'errors' flag
            if (!empty($response['failures'])) {
                $this->logger->error(
                    "[OpenSearch] Partial failures during delete_by_query",
                    ['failures' => $response['failures']]
                );
                throw new VectorStorageException("Failed to delete some vector documents due to errors.");
            }
        }
    }

    // -------------------------------------------------------------------------
    // Document reads
    // -------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getDocument(int|string $id, int $storeId): ?VectorDocumentInterface
    {
        $index = $this->getCollectionName($storeId);

        try {
            $response = $this->getClient()->get([
                'index' => $index,
                'id' => (string) $id,
                'client' => ['ignore' => [404]],
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('[OpenSearch] Failed to get vector document id=%s', $id),
                [
                    'index' => $index,
                    'error' => $e->getMessage()
                ]
            );

            throw new VectorStorageException(
                sprintf("Failed to get vector document id=%s", $id)
            );
        }

        if (empty($response['found'])) {
            return null;
        }

        return $this->bodyToDocument($id, $response['_source'] ?? []);
    }

    /**
     * @inheritDoc
     */
    public function getProductIdsBatched(int $storeId, int $batchSize = 1000): \Generator
    {
        $index = $this->getCollectionName($storeId);

        // Keep the scroll context alive for 2 minutes between batch requests
        $scrollTimeout = '2m';

        // 1. Initial Search Request
        $params = [
            'index' => $index,
            'scroll' => $scrollTimeout,
            'size' => $batchSize,
            'body' => [
                'query' => ['match_all' => new \stdClass()],
                '_source' => ['metadata.product_id'], // only pull product_id field
                // CRITICAL: Sorting by _doc is significantly faster for deep scrolling than scoring.
                'sort' => ['_doc']
            ]
        ];

        $response = $this->client->search($params);
        $scrollId = $response['_scroll_id'] ?? null;

        try {
            while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {

                $productIds = [];
                foreach ($response['hits']['hits'] as $hit) {
                    $productId = $hit['_source']['metadata']['product_id'] ?? null;

                    if ($productId === null) {
                        continue;
                    }

                    $productIds[] = (int) $productId;
                }

                // Yield the batch back to the caller
                yield $productIds;

                // 2. Request the next batch using the Scroll ID
                $response = $this->client->scroll([
                    'scroll_id' => $scrollId,
                    'scroll' => $scrollTimeout
                ]);

                // Update the scroll ID for the next iteration (it can change)
                $scrollId = $response['_scroll_id'] ?? null;
            }
        } finally {
            // 3. ALWAYS clear the scroll context when done or if an error occurs.
            // OpenSearch holds resources in memory for active scrolls.
            if ($scrollId) {
                $this->client->clearScroll([
                    'scroll_id' => $scrollId
                ]);
            }
        }
    }

    /**
     * Searches for the k nearest documents of the given query vector.
     *
     * @param integer $storeId
     * @param float[] $queryVector   The embedding to search against.
     * @param int     $k             Maximum number of results to return.
     * @param float   $minScore      Minimum threshold score
     * @param array   $filters       Optional metadata filters (back-end-specific DSL fragment).
     * @return VectorDocumentInterface[]  Ordered by descending similarity score.
     * @throws VectorStorageException
     */
    public function searchNearestDocuments(
        int $storeId,
        array $queryVector,
        int $k = 10,
        float $minScore = 0.75,
        array $filters = []
    ): array {
        $index = $this->getCollectionName($storeId);

        $knnClause = [
            'vector' => [
                'vector' => $queryVector,
                'k' => $k,
            ],
        ];

        $query = empty($filters)
            ? ['knn' => $knnClause]
            : [
                'bool' => [
                    'must' => [['knn' => $knnClause]],
                    'filter' => $filters,
                ],
            ];

        try {
            $response = $this->getClient()->search([
                'index' => $index,
                'body' => [
                    'size' => $k,
                    'min_score' => $minScore,
                    'query' => $query,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                "[OpenSearch] Vector kNN search failed",
                [
                    'index' => $index,
                    'error' => $e->getMessage()
                ]
            );

            throw new VectorStorageException("Vector kNN search failed");
        }

        $documents = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $doc = $this->bodyToDocument($hit['_id'], $hit['_source'] ?? []);

            // Attach the relevance score as metadata so callers can use it for hybrid scoring.
            $metadata = $doc->getMetadata();
            $metadata['_score'] = $hit['_score'];
            $doc->setMetadata($metadata);

            $documents[] = $doc;
        }

        return $documents;
    }

    /**
     * @inheritDoc
     */
    public function searchProducts(
        int $storeId,
        array $queryVector,
        int $k = 10,
        float $minScore = 0.75,
        array $filters = []
    ): array {
        $index = $this->getCollectionName($storeId);

        $knnClause = [
            'vector' => [
                'vector' => $queryVector,
                'k' => $k,
            ],
        ];

        $query = empty($filters)
            ? ['knn' => $knnClause]
            : [
                'bool' => [
                    'must' => [['knn' => $knnClause]],
                    'filter' => $filters,
                ],
            ];

        try {
            $response = $this->getClient()->search([
                'index' => $index,
                'body' => [
                    'size' => $k,
                    'min_score' => $minScore,
                    '_source' => ['metadata.product_id'], // only pull product_id field
                    'query' => $query,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                "[OpenSearch] Vector kNN search failed",
                [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]
            );

            throw new VectorStorageException("Vector kNN search failed");
        }

        $results = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $productId = $hit['_source']['metadata']['product_id'] ?? null;

            if ($productId === null) {
                continue;
            }

            $results[] = [
                'product_id' => (int) $productId,
                'score' => $hit['_score'],
            ];
        }

        return $results;
    }

    /**
     * @inheritDoc
     */
    public function refreshCollection(int $storeId): void
    {
        $this->getClient()->indices()->refresh([
            'index' => $this->getCollectionName($storeId)
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Converts a VectorDocumentInterface to the OpenSearch document body array.
     *
     * @param VectorDocumentInterface $document
     * @return array{vector: float[], metadata: array<string, scalar>}
     */
    protected function documentToBody(VectorDocumentInterface $document): array
    {
        return [
            'vector' => $document->getVector(),
            'metadata' => $document->getMetadata(),
        ];
    }

    /**
     * Hydrates a VectorDocumentInterface from an OpenSearch _source array.
     *
     * @param string $id
     * @param array $source
     */
    protected function bodyToDocument(string $id, array $source): VectorDocumentInterface
    {
        /** @var VectorDocumentInterface $doc */
        $doc = $this->documentFactory->create();
        $doc->setId($id);
        $doc->setVector((array) ($source['vector'] ?? []));
        $doc->setMetadata((array) ($source['metadata'] ?? []));

        return $doc;
    }

    /**
     * Logs per-item errors returned by a bulk response.
     *
     * @param array $items
     */
    protected function logBulkErrors(array $items): void
    {
        foreach ($items as $item) {
            foreach ($item as $operation => $detail) {
                if (!empty($detail['error'])) {
                    $this->logger->error(
                        '[OpenSearch] Bulk vector operation error',
                        [
                            'operation' => $operation,
                            'id' => $detail['_id'] ?? 'unknown',
                            'error' => $detail['error'],
                        ]
                    );
                }
            }
        }
    }
}
