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

namespace MageSetu\SemanticSearch\Model\Queue;

use Magento\Framework\Serialize\SerializerInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Model\Service\EmbeddingGenerator;

/**
 * Class Consumer used to generate embeddings from the published messages
 *
 * @since 1.0.0
 */
class GenerateEmbeddingConsumer
{
    /**
     * Class constructor
     *
     * @param SerializerInterface $serializer
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param EmbeddingGenerator $embeddingGenerator
     */
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected readonly ConfigInterface $config,
        protected readonly Logger $logger,
        protected readonly EmbeddingGenerator $embeddingGenerator
    ) {
    }

    /**
     * Consumer process start
     *
     * @param string $request [json value containing store id and products data]
     * @return void
     */
    public function process($request): void
    {
        $requestArr = $this->serializer->unserialize($request);

        if (!$this->isValidRequest($requestArr)) {
            return;
        }

        $storeId = $requestArr['store_id'];
        $products = $requestArr['products'];

        $this->logger->info("[GenerateEmbeddingConsumer] Embedding generation starting...");
        $this->embeddingGenerator->generate($products, $storeId);
        $this->logger->info("[GenerateEmbeddingConsumer] Embedding generation completed.");
    }

    /**
     * Validate consumer request
     *
     * @param array $request
     * @return boolean
     */
    protected function isValidRequest(array $request): bool
    {
        if (!isset($request['store_id'])) {
            $this->logger->error(
                "[GenerateEmbeddingConsumer] Skipping consumer as store id not found.",
                ["request" => $request]
            );

            return false;
        }

        $storeId = $request['store_id'];

        if (!$this->config->isModuleEnabled($storeId)) {
            $this->logger->debug(
                "[GenerateEmbeddingConsumer] Skipping consumer as module is disabled for the store.",
                ["store_id" => $storeId]
            );

            return false;
        }

        if (!isset($request['products'])) {
            $this->logger->error(
                "[GenerateEmbeddingConsumer] Skipping consumer as product data not found.",
                ["request" => $request]
            );

            return false;
        }

        return true;
    }
}
