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

use Magento\Framework\Model\AbstractModel;
use MageSetu\SemanticSearch\Api\Data\ProductInterface;
use Magento\Framework\DataObject\IdentityInterface;
use MageSetu\SemanticSearch\Model\ResourceModel\Product as ProductResource;

/**
 * Semantic Search Product Model
 */
class Product extends AbstractModel implements ProductInterface, IdentityInterface
{
    /**
     * Model cache tag
     */
    public const CACHE_TAG = 'magesetu_semantic_search_product';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'magesetu_semantic_search_product';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ProductResource::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getIdentifier()];
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        return $this->getData(self::ID) ? (int) $this->getData(self::ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setId($id): self
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getProductId(): int
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getContentHash(): string
    {
        return (string) $this->getData(self::CONTENT_HASH);
    }

    /**
     * @inheritDoc
     */
    public function setContentHash(string $contentHash): self
    {
        return $this->setData(self::CONTENT_HASH, $contentHash);
    }

    /**
     * @inheritDoc
     */
    public function getEmbeddingStatus(): int
    {
        return (int) $this->getData(self::EMBEDDING_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setEmbeddingStatus(int $embeddingStatus): self
    {
        return $this->setData(self::EMBEDDING_STATUS, $embeddingStatus);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): ?string
    {
        $message = $this->getData(self::MESSAGE);
        return $message !== null ? (string) $message : null;
    }

    /**
     * @inheritDoc
     */
    public function setMessage(?string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
