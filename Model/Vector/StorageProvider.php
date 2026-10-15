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

use MageSetu\SemanticSearch\Api\VectorStorageProviderInterface;
use MageSetu\SemanticSearch\Api\VectorStoragePoolInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Api\VectorStorageInterface;
use MageSetu\SemanticSearch\Exception\VectorStorageNotFoundException;

/**
 * Storage client provider
 *
 * @since 1.0.0
 */
class StorageProvider implements VectorStorageProviderInterface
{
    /**
     * Class constructor
     *
     * @param VectorStoragePoolInterface $vectorStoragePool
     * @param ConfigInterface $config
     * @param Logger $logger
     */
    public function __construct(
        protected readonly VectorStoragePoolInterface $vectorStoragePool,
        protected readonly ConfigInterface $config,
        protected readonly Logger $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getStorageClient(): VectorStorageInterface
    {
        $provider = $this->config->getVectorDbStorage();

        try {
            return $this->vectorStoragePool->getByCode($provider);
        } catch (\Exception $e) {
            $this->logger->error(
                "Error while getting the vector storage client.",
                [
                    'error' => $e->getMessage(),
                    'provider' => $provider
                ]
            );

            throw new VectorStorageNotFoundException("Vector storage not found.");
        }
    }
}
