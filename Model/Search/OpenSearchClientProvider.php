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

namespace MageSetu\SemanticSearch\Model\Search;

use OpenSearch\GuzzleClientFactory;
use OpenSearch\Client;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Exception\VectorStorageException;

class OpenSearchClientProvider
{
    /**
     * Cached client instance.
     *
     * @var ?Client
     */
    private ?Client $client = null;

    /**
     * Class constructor
     *
     * @param ConfigInterface $config
     * @param Logger $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * Returns configured OpenSearch client instance.
     *
     * @throws \RuntimeException
     */
    public function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $clientConfig = [
            'base_uri' => $this->config->getOpenSearchUrl(),
            'timeout' => $this->config->getOpenSearchTimeout()
        ];

        if ($this->config->isOpenSearchAuthEnabled()) {
            $clientConfig['auth'] = [
                $this->config->getOpenSearchUsername(),
                $this->config->getOpenSearchPassword()
            ];
        }

        try {
            $factory = new GuzzleClientFactory();
            $this->client = $factory->create($clientConfig);

            return $this->client;
        } catch (\Throwable $e) {
            $this->logger->error(
                '[SemanticSearch] OpenSearch client creation failed',
                [
                    'error' => $e->getMessage()
                ]
            );

            throw new VectorStorageException('Unable to initialize OpenSearch client');
        }
    }
}
