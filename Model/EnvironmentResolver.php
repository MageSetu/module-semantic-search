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

use MageSetu\SemanticSearch\Api\EnvironmentResolverInterface;
use Magento\Framework\App\DeploymentConfig;
use MageSetu\SemanticSearch\Setup\ConfigOptionsList;

/**
 * Resolves environment values
 *
 * @api
 * @since 1.0.0
 */
class EnvironmentResolver implements EnvironmentResolverInterface
{
    /**
     * Class constructor
     *
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(private readonly DeploymentConfig $deploymentConfig)
    {
    }

    /**
     * Resolves the environment prefix used for vector storage collection naming.
     *
     * Reads from app/etc/env.php via DeploymentConfig (fast, cached, no DB call).
     * If the value was never set with setup:config:set, or someone deleted it
     * manually from env.php, this returns an empty string rather than throwing -
     * callers decide how to handle "unset".
     *
     * @return string
     */
    public function getVectorStorageCollectionPrefix(): string
    {
        $value = $this->deploymentConfig->get(ConfigOptionsList::CONFIG_PATH_COLLECTION_PREFIX);

        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
