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

namespace MageSetu\SemanticSearch\Api;

/**
 * Interface providing semantic search enabled stores
 *
 * @api
 * @since 1.0.0
 */
interface EnabledStoresProviderInterface
{
    /**
     * Returns store IDs that should be reindexed/enabled from the config.
     *
     * @return int[]
     */
    public function getEnabledStoreIds(): array;
}
