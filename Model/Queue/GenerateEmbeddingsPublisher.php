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

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MageSetu\SemanticSearch\Logger\Logger;

/**
 * Queue message publisher for embedding generation
 *
 * @since 1.0.0
 */
class GenerateEmbeddingsPublisher
{
    /**
     * Message queue topic for generating embeddings
     */
    protected const MESSAGE_TOPIC = "magesetu.semanticsearch.generate_embeddings";

    /**
     * Class constructor
     *
     * @param PublisherInterface $publisher
     * @param SerializerInterface $serializer
     * @param Logger $logger
     */
    public function __construct(
        protected readonly PublisherInterface $publisher,
        protected readonly SerializerInterface $serializer,
        protected readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function publish(array $payload): void
    {
        $this->publisher->publish(
            self::MESSAGE_TOPIC,
            $this->serializer->serialize($payload)
        );

        $this->logger->info("[publisher] topic published", [
            "topic" => self::MESSAGE_TOPIC,
            "store_id" => $payload['store_id'],
            "products" => array_column($payload['products'], 'product_id')
        ]);
    }
}
