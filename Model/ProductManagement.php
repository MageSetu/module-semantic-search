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

namespace MageSetu\SemanticSearch\Model;

use MageSetu\SemanticSearch\Api\ProductManagementInterface;
use MageSetu\SemanticSearch\Api\Data\ProductInterface;
use MageSetu\SemanticSearch\Model\ResourceModel\Product as SemanticSearchProductResource;
use MageSetu\SemanticSearch\Model\Service\PayloadProcessor;
use MageSetu\SemanticSearch\Model\Queue\GenerateEmbeddingsPublisher;
use MageSetu\SemanticSearch\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Semantic search product management model
 *
 * @api
 * @since 1.0.0
 */
class ProductManagement implements ProductManagementInterface
{
    /**
     * Class constructor
     *
     * @param SemanticSearchProductResource $semanticSearchProductResource
     * @param PayloadProcessor $payloadProcessor
     * @param GenerateEmbeddingsPublisher $generateEmbeddingsPublisher
     * @param Logger $logger
     */
    public function __construct(
        protected readonly SemanticSearchProductResource $semanticSearchProductResource,
        protected readonly PayloadProcessor $payloadProcessor,
        protected readonly GenerateEmbeddingsPublisher $generateEmbeddingsPublisher,
        protected readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function saveBulkPendingPayloads(array $productsToUpdate, int $storeId): void
    {
        if (empty($productsToUpdate)) {
            return;
        }

        [$bulkInsertData, $messageQueueProducts] = $this->payloadProcessor->prepareBulkPendingPayloads(
            $productsToUpdate,
            $storeId
        );

        if (!empty($bulkInsertData)) {
            try {
                $this->semanticSearchProductResource->persistBulkInsert(
                    $bulkInsertData,
                    [ProductInterface::CONTENT_HASH, ProductInterface::EMBEDDING_STATUS]
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    "Error while saving semantic search products.",
                    [
                        'error' => $e->getMessage(),
                        'store_id' => $storeId,
                        'insert_data' => $bulkInsertData
                    ]
                );

                throw new LocalizedException(
                    __("Error while saving semantic search products into the database.")
                );
            }
        }

        if (!empty($messageQueueProducts)) {
            $messageQueueData = [
                'store_id' => $storeId,
                'products' => $messageQueueProducts
            ];

            try {
                $this->generateEmbeddingsPublisher->publish($messageQueueData);
            } catch (\Exception $e) {
                $this->logger->error(
                    "Error while publishing semantic search products.",
                    [
                        'error' => $e->getMessage(),
                        'payload' => $messageQueueData
                    ]
                );

                throw new LocalizedException(
                    __("Error while publishing semantic search products to embedding generation queue.")
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function markSucceeded(array $rawData, ?int $storeId = null): void
    {
        if (empty($rawData)) {
            return;
        }

        $bulkInsertData = [];

        foreach ($rawData as $item) {
            $bulkInsertData[] = [
                ProductInterface::PRODUCT_ID => $item['product_id'],
                ProductInterface::STORE_ID => $storeId ?? $item['store_id'],
                ProductInterface::EMBEDDING_STATUS => ProductInterface::EMBEDDING_STATUS_SUCCESS,
                ProductInterface::MESSAGE => ""
            ];
        }

        $this->semanticSearchProductResource->persistBulkInsert(
            $bulkInsertData,
            [ProductInterface::EMBEDDING_STATUS, ProductInterface::MESSAGE]
        );
    }

    /**
     * @inheritDoc
     */
    public function markFailed(array $rawData, string $message, ?int $storeId = null): void
    {
        if (empty($rawData)) {
            return;
        }

        $bulkInsertData = [];

        foreach ($rawData as $item) {
            $bulkInsertData[] = [
                ProductInterface::PRODUCT_ID => $item['product_id'],
                ProductInterface::STORE_ID => $storeId ?? $item['store_id'],
                ProductInterface::EMBEDDING_STATUS => ProductInterface::EMBEDDING_STATUS_FAILED,
                ProductInterface::MESSAGE => $message
            ];
        }

        $this->semanticSearchProductResource->persistBulkInsert(
            $bulkInsertData,
            [ProductInterface::EMBEDDING_STATUS, ProductInterface::MESSAGE]
        );
    }
}
