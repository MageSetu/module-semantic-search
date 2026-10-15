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

use Magento\Framework\Data\OptionSourceInterface;
use MageSetu\SemanticSearch\Api\VectorStoragePoolInterface;

/**
 * Source model for the vector storage dropdown.
 */
class VectorStorage implements OptionSourceInterface
{
    /**
     * Class constructor
     *
     * @param VectorStoragePoolInterface $storagePool
     */
    public function __construct(
        private readonly VectorStoragePoolInterface $storagePool
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        /** @var string[] $clients */
        $clients = $this->storagePool->getAvailableCodes();

        $options = [];
        foreach ($clients as $clientCode) {
            $options[] = [
                'value' => $clientCode,
                'label' => __(ucwords(str_replace(['_', '-'], ' ', $clientCode)))
            ];
        }

        return $options;
    }
}
