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

namespace MageSetu\SemanticSearch\Plugin\Model\Product;

use Magento\Catalog\Model\Product\Action;
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;

/**
 * Plugin for product mass attribute updates.
 *
 * Triggers custom indexer after Magento updates
 * product attributes using bulk admin action.
 */
class ActionPlugin
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
     * Reindex products after bulk attribute update.
     *
     * Triggered after:
     * Magento\Catalog\Model\Product\Action::updateAttributes()
     *
     * Example flow:
     * Admin → Catalog → Products → Update Attributes
     *
     * @param  Action $subject
     * @param  Action $result
     * @param  array  $productIds
     * @param  array  $attrData
     * @param  int    $storeId
     * @return Action
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateAttributes(
        Action $subject,
        Action $result,
        array $productIds,
        array $attrData,
        int $storeId
    ): Action {

        if (!$this->config->isModuleEnabled($storeId)) {
            return $result;
        }

        if (empty($productIds)) {
            return $result;
        }

        try {
            if (!$this->processor->isIndexerScheduled()) {
                $this->processor->reindexList($productIds);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                '[MassAttributeUpdate] Custom indexer failed during product mass update.',
                [
                    'indexer' => Processor::INDEXER_ID,
                    'product_ids' => $productIds,
                    'store_id' => $storeId,
                    'exception' => $e
                ]
            );
        }

        return $result;
    }
}
