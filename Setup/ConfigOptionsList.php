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

namespace MageSetu\SemanticSearch\Setup;

use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\Option\TextConfigOption;

/**
 * Registers the "semantic-search-collection-prefix" option for:
 *   bin/magento setup:config:set --semantic-search-collection-prefix="staging"
 *   bin/magento setup:install --semantic-search-collection-prefix="production"
 *
 * The value is written into app/etc/env.php under the
 * "semantic_search/collection_prefix" path,
 * which is NOT part of core_config_data, so it survives DB imports/exports
 * (e.g. cloning prod DB down to staging).
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    public const INPUT_KEY_COLLECTION_PREFIX = 'semantic-search-collection-prefix';
    public const CONFIG_PATH_COLLECTION_PREFIX = 'semantic_search/collection_prefix';
    public const CONFIG_DESCRIPTION =
        "Vector database collection prefix used for MageSetu semantic search" .
        " (e.g. production, staging, dev)";

    /**
     * @inheritDoc
     */
    public function getOptions()
    {
        return [
            new TextConfigOption(
                self::INPUT_KEY_COLLECTION_PREFIX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_COLLECTION_PREFIX,
                self::CONFIG_DESCRIPTION
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        // Only write if the user actually passed the flag. This means running
        // setup:config:set without the flag again will NOT wipe out an existing value.
        if (
            isset($options[self::INPUT_KEY_COLLECTION_PREFIX])
            && trim((string) $options[self::INPUT_KEY_COLLECTION_PREFIX]) !== ''
        ) {
            $configData->set(
                self::CONFIG_PATH_COLLECTION_PREFIX,
                trim((string) $options[self::INPUT_KEY_COLLECTION_PREFIX])
            );
        }

        return [$configData];
    }

    /**
     * @inheritDoc
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        if (isset($options[self::INPUT_KEY_COLLECTION_PREFIX])) {
            $value = trim((string) $options[self::INPUT_KEY_COLLECTION_PREFIX]);

            if ($value === '') {
                $errors[] = sprintf(
                    'Invalid value for --%s: value cannot be empty or whitespace only.',
                    self::INPUT_KEY_COLLECTION_PREFIX
                );
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                $errors[] = sprintf(
                    'Invalid value "%s" for --%s: only alphabets, numbers, and underscores are allowed.',
                    $value,
                    self::INPUT_KEY_COLLECTION_PREFIX
                );
            }
        }

        return $errors;
    }
}
