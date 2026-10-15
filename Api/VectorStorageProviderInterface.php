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

interface VectorStorageProviderInterface
{
    /**
     * Get vector database storage client
     *
     * @return VectorStorageInterface
     * @throws \MageSetu\SemanticSearch\Exception\VectorStorageNotFoundException
     */
    public function getStorageClient(): VectorStorageInterface;
}
