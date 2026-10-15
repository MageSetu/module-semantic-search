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

use MageSetu\SemanticSearch\Exception\VectorStorageNotFoundException;

/**
 * Contract for the dynamic Vector storage pool.
 *
 * The pool is a registry of Magento-generated objects keyed by provider code.
 * It remains fully open for extension: any downstream module can
 * register a new provider factory via its own di.xml virtualType — no base
 * code changes required.
 */
interface VectorStoragePoolInterface
{
    /**
     * Get a storage instance for the given provider code.
     *
     * @param string $code - Provider code (e.g., 'opensearch', 'qdrant', 'chromadb')
     *
     * @throws VectorStorageNotFoundException  When $code is not registered in this pool
     */
    public function getByCode(string $code): VectorStorageInterface;

    /**
     * Return all provider codes registered in this pool.
     *
     * @return string[]
     */
    public function getAvailableCodes(): array;
}
