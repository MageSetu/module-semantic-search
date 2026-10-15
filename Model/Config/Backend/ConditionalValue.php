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

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Dynamic required entry validator for personalized message and embedding settings.
 */
class ConditionalValue extends Value
{
    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $provider = $this->getFieldsetDataValue('provider');
        $field = $this->getField(); // e.g. 'openai_model', 'ollama_model' or 'opensearch_server_url'
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
