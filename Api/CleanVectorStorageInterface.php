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

use MageSetu\SemanticSearch\Exception\VectorStorageException;
use MageSetu\SemanticSearch\Exception\VectorStorageNotFoundException;

/**
 * Helpful implementation when embedding model or provider is changed
 * and you want to rebuild the indexer.
 *
 * @api
 * @since 1.0.0
 */
interface CleanVectorStorageInterface
{
    /**
     * Execute clean embedding in database and vector storage.
     *
     * @param int $storeId
     * @return void
     * @throws VectorStorageException
     * @throws VectorStorageNotFoundException
     */
    public function execute(int $storeId): void;
}
