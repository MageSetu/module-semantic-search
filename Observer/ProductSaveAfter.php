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
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;

/**
 * ProductSaveAfter class
 * observes catalog_product_save_after event
 *
 * @api
 * @since 1.0.0
 */
class ProductSaveAfter implements ObserverInterface
{
    /**
     * Class constructor
     *
     * @param Processor $processor
     * @param ConfigInterface $config
     * @param Logger $logger
     */
    public function __construct(
        private readonly Processor $processor,
        private readonly ConfigInterface $config,
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

        $storeId = (int) $product->getStoreId();

        if (!$this->config->isModuleEnabled($storeId)) {
            return;
        }

        $productId = $product->getId();

        try {
            if (!$this->processor->isIndexerScheduled()) {
                $this->processor->reindexRow($productId);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                '[ProductSaveAfter] Error while reindexing product',
                ['product_id' => $productId, 'exception' => $e]
            );
        }
    }
}
