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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use MageSetu\SemanticSearch\Model\Queue\RemoveEmbeddingsPublisher;
use MageSetu\SemanticSearch\Api\EnabledStoresProviderInterface;
use MageSetu\SemanticSearch\Logger\Logger;

/**
 * ProductDeleteAfter class
 * observes catalog_product_delete_after_done event
 *
 * @api
 * @since 1.0.0
 */
class ProductDeleteAfter implements ObserverInterface
{
    /**
     * Class constructor
     *
     * @param RemoveEmbeddingsPublisher $removeEmbeddingsPublisher
     * @param EnabledStoresProviderInterface $enabledStoresProvider
     * @param Logger $logger
     */
    public function __construct(
        private readonly RemoveEmbeddingsPublisher $removeEmbeddingsPublisher,
        private readonly EnabledStoresProviderInterface $enabledStoresProvider,
        private readonly Logger $logger
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
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        if (!$product || !$product->getId()) {
            return;
        }

        $productId = $product->getId();

        $storeIds = $this->enabledStoresProvider->getEnabledStoreIds();

        foreach ($storeIds as $storeId) {
            $payload = [
                'store_id' => $storeId,
                'product_ids' => [$productId]
            ];
            try {
                $this->removeEmbeddingsPublisher->publish($payload);
            } catch (\Exception $e) {
                $this->logger->error(
                    '[ProductDeleteAfter] Error while reindexing product',
                    ['product_id' => $productId, 'error' => $e->getMessage()]
                );
            }
        }
    }
}
