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

namespace MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Action;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Api\EnabledStoresProviderInterface;
use MageSetu\SemanticSearch\Api\ProductManagementInterface;
use Magento\Catalog\Model\Product as ProductModel;

/**
 * Abstract Action class to process products for Full and Partial Index
 */
abstract class AbstractAction
{
    /**
     * Batch size to generate embeddings in batches
     */
    protected const BATCH_SIZE = 1000;

    /**
     * Class constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param EnabledStoresProviderInterface $enabledStoresProvider
     * @param ProductManagementInterface $productManagement
     */
    public function __construct(
        protected readonly CollectionFactory $collectionFactory,
        protected readonly ConfigInterface $config,
        protected readonly Logger $logger,
        protected readonly EnabledStoresProviderInterface $enabledStoresProvider,
        protected readonly ProductManagementInterface $productManagement
    ) {
    }

    /**
     * Process batch
     *
     * Shared logic to process a collection of products
     *
     * @param array $ids - product ids
     * @param ProductCollection $collection
     * @param int $storeId
     *
     * @return void
     */
    protected function processBatch(array $ids, $collection, $storeId)
    {
        $productsToUpdate = [];

        /** @var ProductModel $product */
        foreach ($collection as $product) {
            $semanticAttributes = $this->getSemanticAttributesData($product);
            ksort($semanticAttributes);

            if (empty($semanticAttributes)) {
                continue; // Skip products with no AI context set
            }

            $productsToUpdate[] = [
                'product_id' => $product->getId(),
                'semantic_attributes' => $semanticAttributes
            ];
        }

        // Bulk insert the available products
        $this->flushBulkInsert($productsToUpdate, $storeId);

        $unavailableProductIds = array_diff($ids, array_column($productsToUpdate, 'product_id'));
        if ($unavailableProductIds) {
            $this->logger->info(
                sprintf("[Indexing] %s products were unavailable.", count($unavailableProductIds)),
                ['skipped_product_ids' => $unavailableProductIds]
            );
        }
    }

    /**
     * Get semantic attributes data for a product
     *
     * @param ProductModel $product
     * @return array
     */
    protected function getSemanticAttributesData(ProductModel $product): array
    {
        $attributeCodes = $this->config->getSemanticAttributes($product->getStoreId());
        if (empty($attributeCodes)) {
            return [];
        }

        $data = [];

        foreach ($attributeCodes as $code) {
            $val = (string) $product->getData($code);

            // Clean the text (remove HTML tags and spaces)
            $val = trim(strip_tags($val));

            if ($val) {
                $data[$code] = $val;
            }
        }

        return $data;
    }

    /**
     * Get batch size for product index processing
     *
     * @param int $storeId
     *
     * @return int
     */
    protected function getBatchSize($storeId): int
    {
        return $this->config->getIndexBatchSize($storeId) ?? self::BATCH_SIZE;
    }

    /**
     * Executes a single, massive INSERT query
     *
     * @param array $productsToUpdate
     * @param int $storeId
     */
    protected function flushBulkInsert(array $productsToUpdate, int $storeId)
    {
        if (empty($productsToUpdate)) {
            return;
        }

        $this->productManagement->saveBulkPendingPayloads($productsToUpdate, $storeId);
    }
}
