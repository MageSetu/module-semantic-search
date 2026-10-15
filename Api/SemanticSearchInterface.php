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
 * Semantic search interface with AI assistance
 * @api
 * @since 1.0.0
 */
interface SemanticSearchInterface
{
    /**
     * Search string with AI
     *
     * @param string $query
     * @param null|int $storeId - Default is null
     * @return Data\SemanticSearchResponseInterface
     */
    public function search(string $query, ?int $storeId = null): Data\SemanticSearchResponseInterface;
}
