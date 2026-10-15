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
 * Represents a single document to be stored in the vector index.
 *
 * Carries the entity identifier, the float embedding vector,
 * and an open-ended metadata bag (e.g. store_id, sku, category_ids).
 *
 * Implement this interface (or extend the default DataObject-backed model)
 * if you need to attach extra fields without touching the storage layer.
 */
interface VectorDocumentInterface
{
    /**
     * Returns the unique identifier for the document in the vector index.
     *
     * Typically the Magento product ID + store id (cast to string).
     */
    public function getId(): int|string;

    /**
     * Set the ID of the document
     *
     * @param int|string $id
     * @return self
     */
    public function setId(int|string $id): self;

    /**
     * Returns the embedding vector as an array of floats.
     *
     * @return float[]
     */
    public function getVector(): array;

    /**
     * Set embedding vector value
     *
     * @param float[] $vector
     */
    public function setVector(array $vector): self;

    /**
     * Returns arbitrary metadata stored alongside the vector.
     * Keys and values must be scalar; nested arrays are not guaranteed
     * to be supported by every storage back-end.
     *
     * @return array<string, scalar>
     */
    public function getMetadata(): array;

    /**
     * Set metadata for the vector
     *
     * @param array $metadata
     */
    public function setMetadata(array $metadata): self;
}
