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

namespace MageSetu\SemanticSearch\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\Exception\LocalizedException;

/**
 * Dynamic required entry validator for personalized message and embedding settings
 * which needs to store values in encrypted format.
 */
class ConditionalEncrypted extends Encrypted
{
    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $provider = $this->getFieldsetDataValue('provider');
        $field = $this->getField(); // e.g. 'openai_api_key' or 'ollama_api_key'
        $fieldProvider = strtok($field, '_');
        $label = $this->getFieldConfig()['label'];

        if ($provider === $fieldProvider && !$this->getValue()) {
            throw new LocalizedException(
                __('"%1" is required when provider is set to "%2".', $label, $provider)
            );
        }

        return parent::beforeSave();
    }
}
