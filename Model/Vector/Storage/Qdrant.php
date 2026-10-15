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
use MageSetu\Common\Api\HttpClientInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Exception\VectorStorageException;

/**
 * Qdrant implementation of VectorStorageInterface.
 *
 * Uses the Qdrant api under the hood.
 */
class Qdrant extends AbstractStorage
{
    protected const CODE = 'qdrant';

    /**
     * Class constructor
     *
     * @param EnvironmentResolverInterface $environmentResolver
     * @param VectorDocumentInterfaceFactory $documentFactory
     * @param Logger $logger
     * @param HttpClientInterface $http
     * @param ConfigInterface $config
     */
    public function __construct(
        EnvironmentResolverInterface $environmentResolver,
        VectorDocumentInterfaceFactory $documentFactory,
        Logger $logger,
        protected readonly HttpClientInterface $http,
        protected readonly ConfigInterface $config,
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
        return false;
    }

    // -------------------------------------------------------------------------
    // Collection lifecycle
    // -------------------------------------------------------------------------

    /**
     * Returns true if the vector collection/index currently exists in the back-end.
     *
     * @param int $storeId
     *
     * @return bool
     * @throws VectorStorageException
     */
    public function collectionExists(int $storeId): bool
    {
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);

        $url = sprintf("%s/collections/%s/exists", $baseUrl, $collection);

        try {
            $response = $this->http->getJson(
                $this->getCode(),
                $url,
                $this->getAuthHeader()
            );

            if (isset($response['status']) && $response['status'] === 'ok') {
                return $response['result']['exists'];
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Error while checking if collection exits in Qdrant database.",
                ['error' => $e->getMessage(), 'collection' => $collection]
            );

            throw new VectorStorageException("[Qdrant] Unable to check collection exits.");
        }

        return false;
    }

    /**
     * Creates the vector collection/index if it does not already exist.
     *
     * Should be idempotent: calling it when the collection/index already exists
     * must not throw or corrupt existing data.
     *
     * @param int   $storeId
     * @param int   $dimensions - Number of dimensions in the embedding vectors.
     * @param array $settings   - Back-end-specific index settings
     *                                         (e.g. number_of_shards, ef_construction).
     *
     * @throws VectorStorageException
     */
    public function createCollectionIfNotExist(int $storeId, int $dimensions, array $settings = []): void
    {
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s", $baseUrl, $collection);

        $body = $this->buildCreateCollectionBody($dimensions, $settings);

        try {
            $response = $this->http->putJson(
                $this->getCode(),
                $url,
                $body,
                $this->getAuthHeader()
            );

            if (isset($response['status']) && $response['status'] === 'ok') {
                return;
            }

            throw new VectorStorageException(
                sprintf("[Qdrant] Unexpected response while creating collection '%s'.", $collection)
            );
        } catch (\Exception $e) {
            if ($e->getCode() === 409 || stripos($e->getMessage(), 'already exists') !== false) {
                return; // idempotent no-op, this is expected and fine, collection already exists.
            }

            $this->logger->error(
                "Error while creating collection in Qdrant database.",
                ['error' => $e->getMessage(), 'collection' => $collection]
            );

            throw new VectorStorageException(
                sprintf("[Qdrant] Unable to create collection '%s'.", $collection)
            );
        }
    }

    /**
     * Deletes the vector collection/index and all its documents.
     *
     * Use with caution – there is no undo.
     *
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function deleteCollection(int $storeId): void
    {
        if (!$this->collectionExists($storeId)) {
            return;
        }

        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s", $baseUrl, $collection);

        try {
            $response = $this->http->deleteJson(
                $this->getCode(),
                $url,
                null,
                $this->getAuthHeader()
            );

            if (isset($response['status']) && $response['status'] === 'ok') {
                return;
            }

            throw new VectorStorageException(
                sprintf("[Qdrant] Unexpected response while deleting collection '%s'.", $collection)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Error while deleting collection in Qdrant database.",
                ['error' => $e->getMessage(), 'collection' => $collection]
            );

            throw new VectorStorageException(
                sprintf("[Qdrant] Unable to delete collection '%s'.", $collection)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refreshCollection(int $storeId): void
    {
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedFunction
    }

    // -------------------------------------------------------------------------
    // Document writes
    // -------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function saveDocument(VectorDocumentInterface $document, int $storeId): void
    {
        $this->saveDocuments([$document], $storeId);
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
     * Handle mass documents save to Qdrant
     *
     * @param VectorDocumentInterface[] $documents
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    protected function handleMassSaveDocuments(array $documents, int $storeId): void
    {
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s/points?wait=true", $baseUrl, $collection);

        $points = array_map([$this, 'toQdrantPoint'], $documents);

        try {
            $response = $this->http->putJson(
                $this->getCode(),
                $url,
                ['points' => $points],
                $this->getAuthHeader()
            );

            if (isset($response['status']) && $response['status'] === 'ok') {
                return;
            }

            throw new VectorStorageException(
                sprintf("[Qdrant] Unexpected response while saving documents to collection '%s'.", $collection)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Error while saving documents to Qdrant database.",
                [
                    'error' => $e->getMessage(),
                    'collection' => $collection,
                    'batch_size' => count($documents),
                ]
            );

            throw new VectorStorageException(
                sprintf("[Qdrant] Unable to save documents to collection '%s'.", $collection)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDocument(int|string $id, int $storeId): void
    {
        $this->deleteDocuments([(int) $id], $storeId);
    }

    /**
     * @inheritDoc
     */
    public function deleteDocuments(array $ids, int $storeId): void
    {
        if (empty($ids)) {
            return;
        }

        $this->massDeleteDocuments($ids, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function deleteDocumentsByProductIds(array $ids, int $storeId): void
    {
        if (empty($ids)) {
            return;
        }

        $this->massDeleteDocuments($ids, $storeId, 'product_id');
    }

    /**
     * Mass delete documents either by document id or product id.
     *
     * @param array $ids
     * @param int $storeId
     * @param string $type
     * @return void
     */
    private function massDeleteDocuments(array $ids, int $storeId, string $type = 'document_id'): void
    {
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s/points/delete?wait=true", $baseUrl, $collection);

        $batches = array_chunk($ids, self::MAX_BULK_DELETE_DOCS);
        foreach ($batches as $batch) {
            if ($type === 'product_id') {
                $payload = [
                    'filter' => [
                        'must' => [
                            [
                                'key' => 'product_id',
                                'match' => [
                                    'any' => array_values($batch)
                                ]
                            ]
                        ]
                    ]
                ];
            } else {
                $payload = ['points' => array_values($batch)];
            }

            try {
                $response = $this->http->postJson(
                    $this->getCode(),
                    $url,
                    $payload,
                    $this->getAuthHeader()
                );

                if (isset($response['status']) && $response['status'] === 'ok') {
                    continue;
                }

                throw new VectorStorageException(
                    sprintf("[Qdrant] Unexpected response while deleting documents from collection '%s'.", $collection)
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    "Error while deleting documents from Qdrant database.",
                    [
                        'error' => $e->getMessage(),
                        'collection' => $collection,
                        'batch_size' => count($batch),
                        'type' => $type,
                        'ids' => $batch,
                    ]
                );

                throw new VectorStorageException(
                    sprintf("[Qdrant] Unable to delete documents from collection '%s'.", $collection)
                );
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
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s/points", $baseUrl, $collection);

        $body = [
            'ids' => [(int) $id],
            'with_payload' => true,
            'with_vector' => true,
        ];

        try {
            $response = $this->http->postJson(
                $this->getCode(),
                $url,
                $body,
                $this->getAuthHeader()
            );

            if (!isset($response['status']) || $response['status'] !== 'ok') {
                throw new VectorStorageException(sprintf(
                    "[Qdrant] Unexpected response while retrieving document '%s' from collection '%s'.",
                    $id,
                    $collection
                ));
            }

            $points = $response['result'] ?? [];

            if (empty($points)) {
                return null;
            }

            return $this->fromQdrantPoint($points[0]);
        } catch (VectorStorageException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(
                "Error while retrieving document from Qdrant database.",
                ['error' => $e->getMessage(), 'collection' => $collection, 'id' => $id]
            );

            throw new VectorStorageException(
                sprintf("[Qdrant] Unable to retrieve document '%s' from collection '%s'.", $id, $collection)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getProductIdsBatched(int $storeId, int $batchSize = 1000): \Generator
    {
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s/points/scroll", $baseUrl, $collection);

        $offset = null;

        do {
            $body = [
                'limit' => $batchSize,
                'with_payload' => ['product_id'],
                'with_vector' => false,
            ];

            if ($offset !== null) {
                $body['offset'] = $offset;
            }

            try {
                $response = $this->http->postJson(
                    $this->getCode(),
                    $url,
                    $body,
                    $this->getAuthHeader()
                );

                if (!isset($response['status']) || $response['status'] !== 'ok') {
                    throw new VectorStorageException(
                        sprintf("[Qdrant] Unexpected response while scrolling collection '%s'.", $collection)
                    );
                }
            } catch (VectorStorageException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->logger->error(
                    "Error while scrolling product IDs from Qdrant database.",
                    ['error' => $e->getMessage(), 'collection' => $collection]
                );

                throw new VectorStorageException(
                    sprintf("[Qdrant] Unable to scroll product IDs from collection '%s'.", $collection)
                );
            }

            $points = $response['result']['points'] ?? [];

            if (!empty($points)) {
                yield array_map(static function (array $point) use ($collection) {
                    if (!isset($point['payload']['product_id'])) {
                        throw new VectorStorageException(
                            sprintf(
                                "[Qdrant] Point '%s' in collection '%s' is missing the 'product_id' payload field.",
                                $point['id'] ?? 'unknown',
                                $collection
                            )
                        );
                    }

                    return $point['payload']['product_id'];
                }, $points);
            }

            $offset = $response['result']['next_page_offset'] ?? null;
        } while ($offset !== null);
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
        $baseUrl = $this->config->getQdrantApiUrl();
        $collection = $this->getCollectionName($storeId);
        $url = sprintf("%s/collections/%s/points/query", $baseUrl, $collection);

        $body = [
            'query' => $queryVector,
            'limit' => $k,
            'score_threshold' => $minScore,
            'with_payload' => ['include' => ['product_id']],
            'with_vector' => false,
        ];

        if (!empty($filters)) {
            $body['filter'] = $filters;
        }

        try {
            $response = $this->http->postJson(
                $this->getCode(),
                $url,
                $body,
                $this->getAuthHeader()
            );

            if (!isset($response['status']) || $response['status'] !== 'ok') {
                throw new VectorStorageException(
                    sprintf("[Qdrant] Unexpected response while searching collection '%s'.", $collection)
                );
            }

            $points = $response['result']['points'] ?? [];

            return array_map([$this, 'toSearchResult'], $points);
        } catch (VectorStorageException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(
                "Error while searching products in Qdrant database.",
                ['error' => $e->getMessage(), 'collection' => $collection, 'k' => $k]
            );

            throw new VectorStorageException(
                sprintf("[Qdrant] Unable to search products in collection '%s'.", $collection)
            );
        }
    }

    /**
     * Maps a VectorDocumentInterface to Qdrant's point payload shape.
     *
     * @param VectorDocumentInterface $document
     *
     * @return array{id: string, vector: float[], payload: array}
     */
    protected function toQdrantPoint(VectorDocumentInterface $document): array
    {
        return [
            'id' => (int) $document->getId(),
            'vector' => $document->getVector(),
            'payload' => $document->getMetadata(),
        ];
    }

    /**
     * Maps a Qdrant point response back into a VectorDocumentInterface.
     *
     * @param array $point array<id: string|int, payload?: array, vector?: array>
     *
     * @return VectorDocumentInterface
     */
    protected function fromQdrantPoint(array $point): VectorDocumentInterface
    {
        /** @var VectorDocumentInterface $doc */
        $doc = $this->documentFactory->create();
        $doc->setId($point['id']);
        $doc->setVector($point['vector'] ?? []);
        $doc->setMetadata($point['payload'] ?? []);

        return $doc;
    }

    /**
     * Maps a Qdrant scored point to a ['product_id' => ..., 'score' => ...] result.
     *
     * @param array $point array{id: string|int, score: float, payload?: array}
     * @return array{product_id: mixed, score: float}
     *
     * @throws VectorStorageException
     */
    protected function toSearchResult(array $point): array
    {
        if (!isset($point['payload']['product_id'])) {
            throw new VectorStorageException(
                sprintf(
                    "[Qdrant] Point '%s' is missing the 'product_id' payload field.",
                    $point['id'] ?? 'unknown'
                )
            );
        }

        return [
            'product_id' => $point['payload']['product_id'],
            'score' => $point['score'],
        ];
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
     * Builds the request body for the Qdrant "create collection" endpoint.
     *
     * @param int   $dimensions
     * @param array $settings
     *
     * @return array
     */
    protected function buildCreateCollectionBody(int $dimensions, array $settings): array
    {
        $body = [
            'vectors' => [
                'size' => $dimensions,
                'distance' => $settings['distance'] ?? 'Cosine',
            ],
        ];

        // Pass through optional, back-end-specific settings only if provided,
        // so we don't override Qdrant's own defaults unnecessarily.
        $optionalKeys = [
            'shard_number',
            'replication_factor',
            'write_consistency_factor',
            'on_disk_payload',
            'hnsw_config',
            'wal_config',
            'optimizers_config',
            'quantization_config',
            'sparse_vectors',
        ];

        foreach ($optionalKeys as $key) {
            if (array_key_exists($key, $settings)) {
                $body[$key] = $settings[$key];
            }
        }

        return $body;
    }

    /**
     * Get authorization header
     *
     * @return array
     */
    protected function getAuthHeader(): array
    {
        if ($apiKey = $this->config->getQdrantApiToken()) {
            return ['api-key' => $apiKey];
        }

        return [];
    }
}
