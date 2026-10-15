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

namespace MageSetu\SemanticSearch\Model\Vector;

use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterface;
use Magento\Framework\DataObject;

/**
 * Default DataObject-backed implementation of VectorDocumentInterface.
 *
 * Using DataObject means the ObjectManager can hydrate instances via
 * setData() / addData(), and it is compatible with Magento's extension
 * attribute system should you need to bolt extra fields on later.
 */
class Document extends DataObject implements VectorDocumentInterface
{
    private const KEY_ID = 'id';
    private const KEY_VECTOR = 'vector';
    private const KEY_METADATA = 'metadata';

    /**
     * @inheritDoc
     */
    public function getId(): int|string
    {
        return $this->getData(self::KEY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId(int|string $id): VectorDocumentInterface
    {
        return $this->setData(self::KEY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getVector(): array
    {
        return (array) ($this->getData(self::KEY_VECTOR) ?? []);
    }

    /**
     * @inheritDoc
     */
    public function setVector(array $vector): VectorDocumentInterface
    {
        return $this->setData(self::KEY_VECTOR, $vector);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        return (array) ($this->getData(self::KEY_METADATA) ?? []);
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(array $metadata): VectorDocumentInterface
    {
        return $this->setData(self::KEY_METADATA, $metadata);
    }
}
