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

namespace MageSetu\SemanticSearch\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Base class to test various connections for semantic search configs
 */
abstract class AbstractTestConnection extends Field
{
    /**
     * Path to template file in theme.
     * @var string
     *
     * @inheritDoc
     */
    protected $_template = 'MageSetu_SemanticSearch::system/config/test_connection.phtml';

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->getPostUrl(),
                'field_mapping' => str_replace('"', '\\"', json_encode($this->getFieldMapping()))
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Returns post url to submit test
     *
     * @return string
     */
    abstract protected function getPostUrl(): string;

    /**
     * Returns configuration fields required to perform the ping request
     *
     * @return array
     */
    abstract protected function getFieldMapping(): array;
}
