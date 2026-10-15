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
use Magento\Catalog\Model\ResourceModel\Product as MagentoProductResource;
use Magento\Store\Model\StoreManagerInterface;
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Execute selected products index for magesetu_semanticsearch_product indexer
 */
class ListAction extends AbstractAction
{
    /**
     * Class constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param EnabledStoresProviderInterface $enabledStoresProvider
     * @param ProductManagementInterface $productManagement
     * @param MagentoProductResource $magentoProductResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigInterface $config,
        Logger $logger,
        EnabledStoresProviderInterface $enabledStoresProvider,
        ProductManagementInterface $productManagement,
        private readonly MagentoProductResource $magentoProductResource,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct(
            $collectionFactory,
            $config,
            $logger,
            $enabledStoresProvider,
            $productManagement
        );
    }

    /**
     * Execute indexing for a list of products
     *
     * Logic runs with below checks
     * - index only stores for which module is enabled.
     * - process only products which belongs to enabled stores/websites.
     *
     * @param array $ids Changed Product Entity IDs
     */
    public function execute(array $ids)
    {
        $ids = array_unique(array_map('intval', $ids));
        if (empty($ids)) {
            return;
        }

        $storeIds = $this->enabledStoresProvider->getEnabledStoreIds();
        if (empty($storeIds)) {
            return;
        }

        try {
            $productWebsiteMap = $this->magentoProductResource->getWebsiteIdsByProductIds($ids);
        } catch (\Exception $e) {
            $this->logger->error(
                __("[indexing] Error fetching product website relations: %1", $e->getMessage())
            );
            return;
        }

        if (empty($productWebsiteMap)) {
            return;
        }

        // Invert: website_id => [product_id, ...]
        $websiteProductMap = [];
        foreach ($productWebsiteMap as $productId => $websiteIds) {
            foreach ($websiteIds as $websiteId) {
                $websiteProductMap[(int) $websiteId][] = (int) $productId;
            }
        }

        foreach ($storeIds as $storeId) {
            try {
                $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
            } catch (\Exception $e) {
                continue;
            }

            $storeProductIds = $websiteProductMap[$websiteId] ?? [];
            if (empty($storeProductIds)) {
                continue;
            }

            $this->doExecute($storeProductIds, $storeId);
        }
    }

    /**
     * Execute indexing for a store
     *
     * @param array $ids Changed Product Entity IDs
     * @param int $storeId
     */
    private function doExecute($ids, $storeId)
    {
        $attributes = $this->config->getSemanticAttributes($storeId);
        if (empty($attributes)) {
            $this->logger->info(
                __("[indexing] Skipping indexing as no semantic attributes selected.")
            );

            return;
        }

        // Even though it's a specific list of IDs, we still batch them.
        // If an admin mass-updates 50,000 products, we still need memory protection!
        $chunks = array_chunk($ids, $this->getBatchSize($storeId));

        foreach ($chunks as $chunkIds) {
            $this->logger->debug(
                "[indexing] Processing semantic embedding product indexing",
                [
                    'batch_size' => count($chunkIds),
                    'store_id' => $storeId
                ]
            );

            /** @var ProductCollection $batchCollection */
            $batchCollection = $this->collectionFactory->create()
                ->addAttributeToSelect($attributes)
                ->setStoreId($storeId)
                ->addStoreFilter($storeId)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter(
                    'visibility',
                    [
                        'in' => [
                            Visibility::VISIBILITY_IN_CATALOG,
                            Visibility::VISIBILITY_IN_SEARCH,
                            Visibility::VISIBILITY_BOTH
                        ]
                    ]
                )
                ->addAttributeToFilter('entity_id', ['in' => $chunkIds]);

            try {
                $this->processBatch($chunkIds, $batchCollection, $storeId);
            } catch (\Exception $e) {
                $this->logger->error(
                    __("[indexing] Error while indexing custom indexer"),
                    [
                        'error' => $e->getMessage(),
                        'indexer' => Processor::INDEXER_ID,
                        'indexer_type' => 'List',
                        'store_id' => $storeId,
                        'productIds' => $chunkIds
                    ]
                );
            }

            // Clear memory
            $batchCollection->clear();
            $this->logger->debug(
                "[indexing] Semantic embedding product indexing completed.",
                [
                    'batch_size' => count($chunkIds),
                    'store_id' => $storeId
                ]
            );
        }
    }
}
