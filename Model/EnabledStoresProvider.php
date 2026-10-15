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

use MageSetu\SemanticSearch\Api\EnabledStoresProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;

/**
 * Enabled Stores Provider Model
 */
class EnabledStoresProvider implements EnabledStoresProviderInterface
{
    /**
     * Class constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ConfigInterface $config,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getEnabledStoreIds(): array
    {
        $storeIds = [];

        /** @var \Magento\Store\Model\Store $store */
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            if (!$store->isActive()) {
                continue;
            }

            if (!$this->config->isModuleEnabled($storeId)) {
                continue;
            }

            $storeIds[] = $storeId;
        }

        return $storeIds;
    }
}
