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

namespace MageSetu\SemanticSearch\Model\Service;

use MageSetu\SemanticSearch\Api\VectorStorageProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Api\VectorStorageInterface;

class OrphanedEmbeddingCleaner
{
    /**
     * Class constructor
     *
     * @param VectorStorageProviderInterface $storageProvider
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param Logger $logger
     */
    public function __construct(
        private readonly VectorStorageProviderInterface $storageProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection $resourceConnection,
        private readonly Logger $logger
    ) {
    }

    /**
     * Executes the cleanup for a specific store.
     *
     * @param int $storeId
     * @param int $batchSize
     * @param bool $dryRun
     * @return int number of documents removed
     */
    public function cleanForStore(int $storeId, int $batchSize = 1000, bool $dryRun = false): int
    {
        try {
            /** @var VectorStorageInterface $vectorStorage */
            $vectorStorage = $this->storageProvider->getStorageClient();

            if (!$vectorStorage->collectionExists($storeId)) {
                $this->logger->error(
                    "[OrphanedEmbeddingCleaner] collection does not exists for store id {$storeId}. Nothing to clean."
                );

                return 0;
            }

            $orphanedChunks = [];

            $batchGenerator = $vectorStorage->getProductIdsBatched($storeId, $batchSize);
            foreach ($batchGenerator as $vectorStorageProductIds) {
                if (empty($vectorStorageProductIds)) {
                    continue;
                }

                $orphanedChunk = $this->getMissingProductIdsInMagento($vectorStorageProductIds, $storeId);
                if (!empty($orphanedChunk)) {
                    $orphanedChunks[] = $orphanedChunk;
                }
            }

            if (empty($orphanedChunks)) {
                return 0;
            }

            $orphanedProductIds = array_merge(...$orphanedChunks);

            if ($dryRun) {
                $this->logger->info(
                    sprintf("Total %d documents are orphaned in store %d", count($orphanedProductIds), $storeId)
                );

                return count($orphanedProductIds);
            } else {
                $vectorStorage->deleteDocumentsByProductIds($orphanedProductIds, $storeId);

                if ($vectorStorage->needsRefreshForBulkOperations()) {
                    $vectorStorage->refreshCollection($storeId);
                }

                return count($orphanedProductIds);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "[OrphanedEmbeddingCleaner] Error while removing orphaned embeddings.",
                [
                    "store_id" => $storeId,
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

    /**
     * Get missing Magento product ids
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    private function getMissingProductIdsInMagento(array $productIds, int $storeId): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
        $tableName = $this->resourceConnection->getTableName('catalog_product_website');

        $existingIds = $connection->fetchCol(
            $connection->select()
                ->from($tableName, 'product_id')
                ->where('website_id = ?', $websiteId)
                ->where('product_id IN (?)', $productIds)
        );

        return array_diff($productIds, $existingIds);
    }
}
