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

namespace MageSetu\SemanticSearch\Model\Queue;

use Magento\Framework\Serialize\SerializerInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Api\VectorStorageProviderInterface;
use MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService;
use MageSetu\SemanticSearch\Api\VectorStorageInterface;
use MageSetu\SemanticSearch\Exception\VectorStorageException;

/**
 * Class Consumer used to remove embeddings from the published messages
 *
 * @since 1.0.0
 */
class RemoveEmbeddingConsumer
{
    /**
     * Class constructor
     *
     * @param SerializerInterface $serializer
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param VectorStorageProviderInterface $storageProvider
     * @param EmbeddingCacheService $embeddingCacheService
     */
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected readonly ConfigInterface $config,
        protected readonly Logger $logger,
        protected readonly VectorStorageProviderInterface $storageProvider,
        protected readonly EmbeddingCacheService $embeddingCacheService
    ) {
    }

    /**
     * Consumer process start
     *
     * @param string $request [json value containing store id and product_ids]
     * @return void
     */
    public function process($request): void
    {
        $requestArr = $this->serializer->unserialize($request);

        if (!$this->isValidRequest($requestArr)) {
            return;
        }

        $storeId = $requestArr['store_id'];
        $productsIds = $requestArr['product_ids'];

        try {
            /** @var VectorStorageInterface $vectorStorage */
            $vectorStorage = $this->storageProvider->getStorageClient();

            if (!$vectorStorage->collectionExists($storeId)) {
                throw new VectorStorageException("Vector collection does not exits.");
            }

            $vectorStorage->deleteDocumentsByProductIds($productsIds, $storeId);

            if ($vectorStorage->needsRefreshForBulkOperations()) {
                $vectorStorage->refreshCollection($storeId);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "[RemoveEmbeddingConsumer] Error while removing embeddings.",
                [
                    'error' => $e->getMessage(),
                    "store_id" => $storeId,
                    "product_ids" => $productsIds
                ]
            );

            return;
        }

        // Clean embedding cache
        $this->embeddingCacheService->cleanCacheForProductsInStores([$storeId => $productsIds]);

        $this->logger->info(
            "[RemoveEmbeddingConsumer] Embeddings removed successfully.",
            [
                "store_id" => $storeId,
                "product_ids" => $productsIds
            ]
        );
    }

    /**
     * Validate consumer request
     *
     * @param array $request
     * @return boolean
     */
    protected function isValidRequest(array $request): bool
    {
        if (!isset($request['store_id'])) {
            $this->logger->error(
                "[RemoveEmbeddingConsumer] Skipping consumer as store id not found.",
                ["request" => $request]
            );

            return false;
        }

        $storeId = $request['store_id'];

        if (!$this->config->isModuleEnabled($storeId)) {
            $this->logger->debug(
                "[RemoveEmbeddingConsumer] Skipping consumer as module is disabled for the store.",
                ["store_id" => $storeId]
            );

            return false;
        }

        if (!isset($request['product_ids'])) {
            $this->logger->error(
                "[RemoveEmbeddingConsumer] Skipping consumer as product ids not found.",
                ["request" => $request]
            );

            return false;
        }

        return true;
    }
}
