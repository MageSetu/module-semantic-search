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

namespace MageSetu\SemanticSearch\Model\Vector;

use MageSetu\SemanticSearch\Api\CleanVectorStorageInterface;
use MageSetu\SemanticSearch\Model\ResourceModel\Product as SemanticSearchProductResource;
use MageSetu\SemanticSearch\Api\VectorStorageProviderInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Exception\VectorStorageException;

/**
 * Clean vector storage implementation
 */
class CleanVectorStorage implements CleanVectorStorageInterface
{
    /**
     * Class constructor
     *
     * @param SemanticSearchProductResource $resource
     * @param VectorStorageProviderInterface $storageProvider
     * @param Logger $logger
     */
    public function __construct(
        private readonly SemanticSearchProductResource $resource,
        private readonly VectorStorageProviderInterface $storageProvider,
        private readonly Logger $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(int $storeId): void
    {
        try {
            $storageClient = $this->storageProvider->getStorageClient();
            $storageClient->deleteCollection($storeId);

            $this->logger->debug("[VectorStorage] Vector storage collection deleted successfully.");

            $this->resource->deleteAllProductsFromStoreId($storeId);
            $this->logger->debug("[VectorStorage] Semantic search product table truncated successfully.");
        } catch (\Exception $e) {
            $this->logger->error(
                "[VectorStorage] Error while cleaning up the vector storage.",
                [
                    "error" => $e->getMessage(),
                    "store_id" => $storeId
                ]
            );

            throw new VectorStorageException("Error while cleaning the vector storage.");
        }
    }
}
