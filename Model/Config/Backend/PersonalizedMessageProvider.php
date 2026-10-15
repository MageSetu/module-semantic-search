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
 * Represent magesetu_semantic_search/personalized_message/provider store configuration
 */
class PersonalizedMessageProvider extends Value
{
    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $enabled = $this->getFieldsetDataValue('enabled');

        if ($enabled && !$this->getValue()) {
            throw new LocalizedException(
                __('Model Provider is required when personalized message is enabled.')
            );
        }

        return parent::beforeSave();
    }
}
