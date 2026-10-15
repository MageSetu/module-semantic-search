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

namespace MageSetu\SemanticSearch\Api\Data;

/**
 * Semantic Search Product Interface
 *
 * @api
 */
interface ProductInterface
{
    /**
     * Constants for field names
     */
    public const ID = 'id';
    public const PRODUCT_ID = 'product_id';
    public const STORE_ID = 'store_id';
    public const CONTENT_HASH = 'content_hash';
    public const EMBEDDING_STATUS = 'embedding_status';
    public const MESSAGE = 'message';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Embedding status constants
     */
    public const EMBEDDING_STATUS_PENDING = 0;
    public const EMBEDDING_STATUS_SUCCESS = 1;
    public const EMBEDDING_STATUS_FAILED = 2;

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set ID
     *
     * @param mixed $id
     * @return self
     */
    public function setId($id): self;

    /**
     * Get Product ID
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set Product ID
     *
     * @param int $productId
     * @return self
     */
    public function setProductId(int $productId): self;

    /**
     * Get Store ID
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Set Store ID
     *
     * @param int $storeId
     * @return self
     */
    public function setStoreId(int $storeId): self;

    /**
     * Get Content Hash
     *
     * @return string
     */
    public function getContentHash(): string;

    /**
     * Set Content Hash
     *
     * @param string $contentHash
     * @return self
     */
    public function setContentHash(string $contentHash): self;

    /**
     * Get Embedding Status
     *
     * @return int
     */
    public function getEmbeddingStatus(): int;

    /**
     * Set Embedding Status
     *
     * @param int $embeddingStatus
     * @return self
     */
    public function setEmbeddingStatus(int $embeddingStatus): self;

    /**
     * Get Message
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Set Message
     *
     * @param string|null $message
     * @return self
     */
    public function setMessage(?string $message): self;

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self;
}
