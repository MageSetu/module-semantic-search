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
use MageSetu\SemanticSearch\Api\CleanVectorStorageInterface;
use MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService;
use MageSetu\SemanticSearch\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Store\Api\Data\StoreInterface;

/**
 * DeleteVectorCollectionForStore class
 * observes store_delete_commit_after event
 *
 * @api
 * @since 1.0.0
 */
class DeleteVectorCollectionForStore implements ObserverInterface
{
    /**
     * Class constructor
     *
     * @param CleanVectorStorageInterface $cleanVectorStorage
     * @param EmbeddingCacheService $cacheService
     * @param Logger $logger
     */
    public function __construct(
        private readonly CleanVectorStorageInterface $cleanVectorStorage,
        private readonly EmbeddingCacheService $cacheService,
        private readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var StoreInterface|null $store */
        $store = $observer->getEvent()->getStore();

        if ($store === null) {
            return;
        }

        $storeId = (int) $store->getId();

        try {
            $this->cleanVectorStorage->execute($storeId);
        } catch (\Exception $e) {
            $this->logger->error(
                "[DeleteVectorCollectionForStore] Error while deleting vector collection.",
                [
                    "store_id" => $storeId,
                    'exception' => $e
                ]
            );

            return;
        }

        $this->cacheService->cleanCacheForStoreId($storeId);

        $this->logger->info(
            "[DeleteVectorCollectionForStore] Vector collection deleted for store id {$storeId}."
        );
    }
}
