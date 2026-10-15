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
 * Semantic Search Result Product Interface
 *
 * @api
 */
interface SearchResultProductInterface
{
    public const NAME = 'name';
    public const IMAGE_URL = 'image_url';
    public const DESCRIPTION = 'description';
    public const PRICE = 'price';
    public const LINK = 'link';

    /**
     * Get product name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set product name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self;

    /**
     * Get product image URL
     *
     * @return string
     */
    public function getImageUrl(): string;

    /**
     * Set product image URL
     *
     * @param string $url
     * @return self
     */
    public function setImageUrl(string $url): self;

    /**
     * Get product description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Set product description
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self;

    /**
     * Get product price
     *
     * @return string
     */
    public function getPrice(): string;

    /**
     * Set product price
     *
     * @param string $price
     * @return self
     */
    public function setPrice(string $price): self;

    /**
     * Get product link
     *
     * @return string
     */
    public function getLink(): string;

    /**
     * Set product link
     *
     * @param string $link
     * @return self
     */
    public function setLink(string $link): self;
}
