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
 * Standard response type for semantic search requests
 *
 * @api
 * @since 1.0.0
 */
interface SemanticSearchResponseInterface
{
    public const STATUS = 'status';
    public const PRODUCTS = 'products';
    public const MESSAGE = 'message';

    /**
     * Is successful response or the status is true
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Set status of the response
     *
     * @param bool $status
     * @return self
     */
    public function setStatus(bool $status): self;

    /**
     * Get products
     *
     * @return SearchResultProductInterface[]
     */
    public function getProducts(): array;

    /**
     * Set products
     *
     * @param SearchResultProductInterface[] $products
     * @return self
     */
    public function setProducts(array $products): self;

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Set message
     *
     * @param string $message
     * @return self
     */
    public function setMessage(string $message): self;
}
