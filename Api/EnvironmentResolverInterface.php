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
 * Resolves environment values
 *
 * @api
 * @since 1.0.0
 */
interface EnvironmentResolverInterface
{
    /**
     * Resolves the environment prefix used for vector storage collection naming.
     */
    public function getVectorStorageCollectionPrefix(): string;
}
