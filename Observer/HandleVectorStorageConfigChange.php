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

namespace MageSetu\SemanticSearch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Indexer\IndexerRegistry;
use MageSetu\Common\Api\ConfigScopeResolverInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService;
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor;

/**
 * HandleVectorStorageConfigChange class
 * observes admin_system_config_changed_section_magesetu_semantic_search event
 *
 * Represent store configuration changes for:
 *  - magesetu_semantic_search/vectordb/storage
 *
 * @api
 * @since 1.0.0
 */
class HandleVectorStorageConfigChange implements ObserverInterface
{
    private const MODULE_ENABLE_CONFIG_PATH = 'magesetu_semantic_search/general/enabled';

    /**
     * Store configurations need to cleaning vectors
     */
    private const CONFIG_NEEDING_DATA_REMOVE = [
        'magesetu_semantic_search/vectordb/storage'
    ];

    /**
     * Class constructor
     *
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigScopeResolverInterface $configScopeResolver
     * @param Logger $logger
     * @param EmbeddingCacheService $cacheService
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry,
        private readonly ConfigScopeResolverInterface $configScopeResolver,
        private readonly Logger $logger,
        private readonly EmbeddingCacheService $cacheService
    ) {
    }

    /**
     * Event handler method
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $changedPaths = $observer->getEvent()->getData('changed_paths');
        $affectChangedPaths = array_intersect(self::CONFIG_NEEDING_DATA_REMOVE, $changedPaths);
        if (empty($affectChangedPaths)) {
            return;
        }

        $this->logger->debug("[ConfigChange] Vector storage configuration changed.", [
            'affected_changed_paths' => $affectChangedPaths,
            'changed_paths' => $changedPaths
        ]);

        $currentStore = $observer->getEvent()->getData('store');
        $affectedStoreIds = $this->configScopeResolver->getInheritedStoreIds(
            self::MODULE_ENABLE_CONFIG_PATH,
            null,
            $currentStore
        );

        $this->cleanEmbeddingCache($affectedStoreIds);
        $this->invalidateIndexer();
    }

    /**
     * Clean embeddings cache for the affected stores
     *
     * @param array $storeIds
     * @return void
     */
    private function cleanEmbeddingCache(array $storeIds)
    {
        foreach ($storeIds as $storeId) {
            $this->cacheService->cleanCacheForStoreId($storeId);
        }
    }

    /**
     * Invalidate semantic search indexer
     *
     * @return void
     */
    private function invalidateIndexer(): void
    {
        $indexer = $this->indexerRegistry->get(Processor::INDEXER_ID);

        if (!$indexer->isInvalid()) {
            $indexer->invalidate();
        }
    }
}
