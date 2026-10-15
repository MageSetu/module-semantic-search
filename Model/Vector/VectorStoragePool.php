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

use MageSetu\SemanticSearch\Api\VectorStorageInterface;
use MageSetu\SemanticSearch\Api\VectorStoragePoolInterface;
use MageSetu\SemanticSearch\Exception\VectorStorageNotFoundException;
use Magento\Framework\Exception\ConfigurationMismatchException;

/**
 * Dynamic registry of vector storages, keyed by provider code.
 *
 * Adding a new AI provider requires ZERO changes to this class:
 * just register its auto-generated client in the consuming module's di.xml.
 *
 * Example Type declaration (consuming module di.xml):
 *
 *   <type name="MageSetu\SemanticSearch\Model\Vector\VectorStoragePool">
 *       <arguments>
 *           <argument name="storages" xsi:type="array">
 *               <item name="opensearch" xsi:type="object">
 *                   MageSetu\SemanticSearch\Model\Vector\Storage\OpenSearch
 *               </item>
 *           </argument>
 *       </arguments>
 *   </virtualType>
 */
class VectorStoragePool implements VectorStoragePoolInterface
{
    /**
     * Class constructor
     *
     * @param array $storages - Storage clients
     */
    public function __construct(
        private readonly array $storages = [],
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getByCode(string $code): VectorStorageInterface
    {
        if ($code && in_array($code, $this->getAvailableCodes())) {
            $instance = $this->storages[$code];

            if (!$instance instanceof VectorStorageInterface) {
                throw new ConfigurationMismatchException(__(
                    '[SemanticSearch] provider "%1" must produce %2.',
                    $code,
                    VectorStorageInterface::class
                ));
            }

            return $instance;
        }

        throw new VectorStorageNotFoundException(sprintf(
            'Provider "%s" is not registered. Available: [%s]',
            $code,
            implode(', ', array_keys($this->storages)) ?: 'none'
        ));
    }

    /**
     * @inheritDoc
     */
    public function getAvailableCodes(): array
    {
        return array_keys($this->storages);
    }
}
