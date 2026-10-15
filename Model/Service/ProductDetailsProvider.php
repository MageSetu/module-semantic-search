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

namespace MageSetu\SemanticSearch\Model\Service;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoProductCollection;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use MageSetu\SemanticSearch\Api\Data\SearchResultProductInterfaceFactory;
use Magento\Catalog\Model\Product as MagentoProduct;
use MageSetu\SemanticSearch\Api\Data\SearchResultProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Magento product details provider
 *
 * @since 1.0.0
 */
class ProductDetailsProvider
{
    /**
     * Class constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param ImageHelper $imageHelper
     * @param PriceHelper $priceHelper
     * @param SearchResultProductInterfaceFactory $searchResultProductFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly ImageHelper $imageHelper,
        private readonly PriceHelper $priceHelper,
        private readonly SearchResultProductInterfaceFactory $searchResultProductFactory,
    ) {
    }

    /**
     * Get product details by product ids and store id
     *
     * @param array $ids
     * @param int $storeId
     * @return SearchResultProductInterface[]
     */
    public function getDetailsByIdsForStore(array $ids, int $storeId): array
    {
        /** @var MagentoProductCollection $collection */
        $collection = $this->getProductCollection($ids, $storeId);

        $productsById = [];

        /** @var MagentoProduct $product */
        foreach ($collection as $product) {
            /** @var SearchResultProductInterface $productData */
            $productData = $this->searchResultProductFactory->create();
            $productData->setName($product->getName())
                ->setImageUrl($this->imageHelper->init($product, 'ai_search_product_thumbnail_image')->getUrl())
                ->setDescription($product->getShortDescription())
                ->setPrice($this->priceHelper->currency($product->getPrice(), true, false))
                ->setLink($product->getProductUrl());
            ;

            $productsById[$product->getId()] = $productData;
        }

        // Re-order according to the original $ids array
        $products = [];
        foreach ($ids as $id) {
            if (isset($productsById[$id])) {
                $products[] = $productsById[$id];
            }
        }

        return $products;
    }

    /**
     * Get product collection
     *
     * @param array $ids
     * @param int $storeId
     *
     * @return MagentoProductCollection
     */
    protected function getProductCollection(array $ids, int $storeId): MagentoProductCollection
    {
        /** @var MagentoProductCollection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['name', 'short_description', 'thumbnail', 'price'])
            ->setStoreId($storeId)
            ->addIdFilter($ids)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter(
                'visibility',
                [
                    'in' => [
                        Visibility::VISIBILITY_IN_CATALOG,
                        Visibility::VISIBILITY_IN_SEARCH,
                        Visibility::VISIBILITY_BOTH
                    ]
                ]
            );

        return $collection;
    }
}
