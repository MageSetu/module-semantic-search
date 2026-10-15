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

namespace MageSetu\SemanticSearch\Model\Data;

use MageSetu\SemanticSearch\Api\Data\SemanticSearchResponseInterface;
use Magento\Framework\DataObject;

/**
 * Semantic Search Result Model
 * @api
 * @since 1.0.0
 */
class SemanticSearchResponse extends DataObject implements SemanticSearchResponseInterface
{
    /**
     * @inheritDoc
     */
    public function isSuccess(): bool
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(bool $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getProducts(): array
    {
        return $this->getData(self::PRODUCTS);
    }

    /**
     * @inheritDoc
     */
    public function setProducts(array $products): self
    {
        return $this->setData(self::PRODUCTS, $products);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage(string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }
}
