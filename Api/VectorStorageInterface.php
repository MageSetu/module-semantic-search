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

namespace MageSetu\SemanticSearch\Api;

use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterface;
use MageSetu\SemanticSearch\Exception\VectorStorageException;

/**
 * Contract for any vector-database back-end used by the semantic search module.
 */
interface VectorStorageInterface
{
    /**
     * Get code of the vector instance
     *
     * @return string
     */
    public function getCode(): string;

    // -------------------------------------------------------------------------
    // Collection lifecycle
    // -------------------------------------------------------------------------

    /**
     * Get the name of the collection/index per store view
     *
     * @param int $storeId
     * @return string
     */
    public function getCollectionName(int $storeId): string;

    /**
     * Returns true if the vector collection/index currently exists in the back-end.
     *
     * @param int $storeId
     *
     * @return bool
     * @throws VectorStorageException
     */
    public function collectionExists(int $storeId): bool;

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
    public function createCollectionIfNotExist(int $storeId, int $dimensions, array $settings = []): void;

    /**
     * Deletes the vector collection/index and all its documents.
     *
     * Use with caution – there is no undo.
     *
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function deleteCollection(int $storeId): void;

    /**
     * Some vector databases needs manual refresh for data
     * for ex. OpenSearch or ElasticSearch needs for indexes
     * This value helps manually trigger refresh.
     *
     * @return bool
     */
    public function needsRefreshForBulkOperations(): bool;

    /**
     * Some vector databases needs manual refresh for data
     * for ex. OpenSearch or ElasticSearch needs for indexes
     * Implement this method to refresh manually else just return.
     *
     * @param int $storeId
     *
     * @return void
     */
    public function refreshCollection(int $storeId): void;

    // -------------------------------------------------------------------------
    // Document writes
    // -------------------------------------------------------------------------

    /**
     * Inserts or fully replaces a single document in the vector collection/index.
     *
     * @param VectorDocumentInterface $document
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function saveDocument(VectorDocumentInterface $document, int $storeId): void;

    /**
     * Inserts or fully replaces multiple documents in a single bulk operation.
     *
     * Implementations SHOULD prefer a native bulk/batch API for performance.
     * If the back-end does not support batching, fall back to looping saveDocument().
     *
     * @param VectorDocumentInterface[] $documents
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function saveDocuments(array $documents, int $storeId): void;

    /**
     * Removes a single document by its ID.
     *
     * Must not throw if the document does not exist (idempotent delete).
     *
     * @param int|string $id
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function deleteDocument(int|string $id, int $storeId): void;

    /**
     * Removes multiple documents by their IDs in a single bulk operation.
     *
     * @param string[] $ids
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function deleteDocuments(array $ids, int $storeId): void;

    /**
     * Removes multiple documents by product IDs in a single bulk operation.
     *
     * @param string[] $ids
     * @param int $storeId
     *
     * @throws VectorStorageException
     */
    public function deleteDocumentsByProductIds(array $ids, int $storeId): void;

    // -------------------------------------------------------------------------
    // Document reads
    // -------------------------------------------------------------------------

    /**
     * Retrieves a single document by its ID, or null if not found.
     *
     * @param int|string $id
     * @param integer $storeId
     *
     * @return VectorDocumentInterface|null
     * @throws VectorStorageException
     */
    public function getDocument(int|string $id, int $storeId): ?VectorDocumentInterface;

    /**
     * Yield batches of product IDs from the vector database.
     *
     * @param int $storeId
     * @param int $batchSize
     * @return \Generator|array[]
     */
    public function getProductIdsBatched(int $storeId, int $batchSize = 1000): \Generator;

    /**
     * Searches for the k nearest products of the given query vector and Returns product ids only.
     *
     * @param integer $storeId
     * @param float[] $queryVector   The embedding to search against.
     * @param int     $k             Maximum number of results to return.
     * @param float   $minScore      Minimum threshold score
     * @param array   $filters       Optional metadata filters (back-end-specific DSL fragment).
     * @return array - product ids and it's score
     * @throws VectorStorageException
     */
    public function searchProducts(
        int $storeId,
        array $queryVector,
        int $k = 10,
        float $minScore = 0.75,
        array $filters = []
    ): array;
}
