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

namespace MageSetu\SemanticSearch\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the Ai search context product attribute Provider dropdown.
 */
class AiSearchContextAttributeProvider implements OptionSourceInterface
{
    /**
     * Constructor.
     *
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $attributeCollectionFactory
    ) {
    }

    /**
     * Return text-based product attributes.
     *
     * Filters only varchar/text backend type and
     * text/textarea frontend input attributes.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        $collection = $this->attributeCollectionFactory->create();

        $collection->addFieldToFilter(
            'backend_type',
            ['in' => ['varchar', 'text']]
        );

        $collection->addFieldToFilter(
            'frontend_input',
            ['in' => ['text', 'textarea']]
        );

        $collection->setOrder('frontend_label', 'ASC');

        $options = [];

        /** @var AbstractAttribute $attribute */
        foreach ($collection as $attribute) {
            $label = $attribute->getFrontendLabel();

            /*
             * Skip attributes without label
             */
            if (!$label) {
                continue;
            }

            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => sprintf(
                    '%s (%s)',
                    $label,
                    $attribute->getAttributeCode()
                )
            ];
        }

        return $options;
    }
}
