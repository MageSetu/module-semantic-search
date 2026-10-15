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

namespace MageSetu\SemanticSearch\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * Create ai search context product attribute.
 *
 * Creates a product attribute used to store semantic embedding source text
 * Can be used for other AI search
 * and assigns it to a dedicated attribute group across all product attribute sets.
 */
class AddAiSearchContextAttribute implements DataPatchInterface
{
    /**
     * Attribute code.
     */
    private const ATTRIBUTE_CODE = 'ai_search_context';

    /**
     * Attribute label.
     */
    private const ATTRIBUTE_LABEL = 'Ai Search Context';

    /**
     * Attribute group name.
     */
    private const ATTRIBUTE_GROUP = 'MageSetu Ai Options';

    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * Apply data patch.
     *
     * Creates attribute and assigns it to all product attribute sets
     * under MageSetu Semantic Search group.
     *
     * @return self
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create([
            'setup' => $this->moduleDataSetup
        ]);

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, self::ATTRIBUTE_CODE);

        /*
         * Create product attribute if not exists
         */
        if (!$attributeId) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                self::ATTRIBUTE_CODE,
                [
                    'type' => 'text',
                    'label' => self::ATTRIBUTE_LABEL,
                    'input' => 'textarea',
                    'required' => false,
                    'user_defined' => false,
                    'sort_order' => 90,

                    /*
                     * Store scope because semantic content may vary by store view
                     */
                    'global' => ScopedAttributeInterface::SCOPE_STORE,

                    /*
                     * Admin configuration
                     */
                    'visible' => true,
                    'visible_on_front' => false,
                    'wysiwyg_enabled' => false,
                    'is_html_allowed_on_front' => false,

                    /*
                     * Search/index behavior
                     */
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_in_advanced_search' => false,
                    'used_in_product_listing' => false,
                    'used_for_sort_by' => false,

                    /*
                     * Internal storage
                     */
                    'unique' => false,
                    'default' => null,
                    'group' => self::ATTRIBUTE_GROUP
                ]
            );
        }

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);

        /*
         * Add group + assign attribute to every attribute set
         */
        foreach ($attributeSetIds as $attributeSetId) {
            $attributeGroupId = $eavSetup->getAttributeGroupId(
                $entityTypeId,
                $attributeSetId,
                self::ATTRIBUTE_GROUP
            );

            if (!$attributeGroupId) {
                $eavSetup->addAttributeGroup(
                    $entityTypeId,
                    $attributeSetId,
                    self::ATTRIBUTE_GROUP,
                    800
                );
            }

            $eavSetup->addAttributeToSet(
                $entityTypeId,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                self::ATTRIBUTE_CODE
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Get patch dependencies.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
