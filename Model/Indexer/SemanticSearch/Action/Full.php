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
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Execute full index for magesetu_semanticsearch_product indexer
 */
class Full extends AbstractAction
{
    /**
     * Class constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param EnabledStoresProviderInterface $enabledStoresProvider
     * @param ProductManagementInterface $productManagement
     * @param QueryGenerator $queryGenerator
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigInterface $config,
        Logger $logger,
        EnabledStoresProviderInterface $enabledStoresProvider,
        ProductManagementInterface $productManagement,
        private readonly QueryGenerator $queryGenerator
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
     * Execute indexing
     *
     * @return void
     */
    public function execute()
    {
        $storeIds = $this->enabledStoresProvider->getEnabledStoreIds();
        foreach ($storeIds as $storeId) {
            $this->doExecute($storeId);
        }
    }

    /**
     * Execute indexing for a store
     *
     * @param int $storeId
     */
    private function doExecute($storeId)
    {
        $attributes = $this->config->getSemanticAttributes($storeId);
        if (empty($attributes)) {
            $this->logger->info(
                __("[indexing] Skipping indexing as no semantic attributes selected.")
            );

            return;
        }

        $this->logger->debug(
            "[indexing] Full semantic embedding product indexing started."
        );

        // process all products by chunks
        foreach ($this->getAvailableProductIdsByStoreId($storeId) as $chunkIds) {
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
                ->addAttributeToFilter('entity_id', ['in' => $chunkIds]);

            try {
                $this->processBatch($chunkIds, $batchCollection, $storeId);
            } catch (\Exception $e) {
                $this->logger->error(
                    __("[indexing] Error while indexing custom indexer"),
                    [
                        'indexer' => Processor::INDEXER_ID,
                        'indexer_type' => 'Full',
                        'error' => $e->getMessage(),
                        'store_id' => $storeId,
                        'product_ids' => $chunkIds
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

        $this->logger->debug(
            "[indexing] Full semantic embedding product indexing completed."
        );
    }

    /**
     * Get available product ids for a store [technically per website]
     *
     * @param int $storeId
     * @return \Generator|int[][]
     */
    protected function getAvailableProductIdsByStoreId($storeId): \Generator
    {
        $collection = $this->collectionFactory->create()
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
            );

        $select = clone $collection->getSelect();

        // 'e' must match the main table alias the product collection uses internally
        $batchSelects = $this->queryGenerator->generate(
            'entity_id',
            $select,
            $this->getBatchSize($storeId),
            'e'
        );

        foreach ($batchSelects as $batchSelect) {
            $ids = $collection->getConnection()->fetchCol($batchSelect);

            if (empty($ids)) {
                continue;
            }

            yield $ids;
        }
    }
}
