---
type: configuration
name: Semantic Search queue consumer configuration
description: Documents the Magento message queue consumers that process asynchronous embedding generation and removal jobs for semantic search.
tags: [magento2, magesetu, semantic-search, queue, consumer, message-queue]
module: MageSetu_SemanticSearch
---

# Semantic Search Queue Consumer Configuration

This document captures the message-queue wiring used by the semantic search module to process embedding work asynchronously outside the request lifecycle.

## Scope

The configuration is defined in:

- <magento-root>/app/code/MageSetu/SemanticSearch/etc/communication.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/queue_consumer.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/queue_publisher.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/queue_topology.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/di.xml

## Message topics and publisher wiring

The module defines two message topics in <magento-root>/app/code/MageSetu/SemanticSearch/etc/communication.xml:

- `magesetu.semanticsearch.generate_embeddings`
- `magesetu.semanticsearch.remove_embeddings`

The publisher wiring in <magento-root>/app/code/MageSetu/SemanticSearch/etc/queue_publisher.xml binds both topics to the Magento exchange so they can be published to the message broker.

### Publisher classes

- `GenerateEmbeddingsPublisher` publishes generation jobs for product payloads using the `magesetu.semanticsearch.generate_embeddings` topic.
- `RemoveEmbeddingsPublisher` publishes deletion jobs for product IDs using the `magesetu.semanticsearch.remove_embeddings` topic.

These publishers are used from the product management flow and from the product delete observer, respectively.

## Consumer registration

Magento registers two queue consumers for the semantic search module.

### 1. Generate embeddings consumer

- Consumer name: `magesetu.semanticsearch.generate_embeddings_consumer`
- Queue: `magesetu.semanticsearch.generate_embeddings`
- Handler: `MageSetu\SemanticSearch\Model\Queue\GenerateEmbeddingConsumer::process`

This consumer processes background requests to generate embeddings for a set of products in a store.

#### Expected payload

The message body is a serialized array containing:

```php
[
    'store_id' => 1,
    'products' => [/* product payloads */],
]
```

#### Runtime behavior

1. The consumer deserializes the incoming payload.
2. It validates that a `store_id` exists and that the module is enabled for that store.
3. It verifies that product data is present.
4. If validation passes, it calls the embedding generator service to create embeddings for the supplied products.

### 2. Remove embeddings consumer

- Consumer name: `magesetu.semanticsearch.remove_embeddings_consumer`
- Queue: `magesetu.semanticsearch.remove_embeddings`
- Handler: `MageSetu\SemanticSearch\Model\Queue\RemoveEmbeddingConsumer::process`

This consumer handles background requests to remove embeddings for products that were deleted or otherwise need their vector data cleaned up.

#### Expected payload

The message body is a serialized array containing:

```php
[
    'store_id' => 1,
    'product_ids' => [101, 102, 103],
]
```

#### Runtime behavior

1. The consumer deserializes the incoming payload.
2. It validates that a `store_id` exists and that the module is enabled for that store.
3. It verifies that `product_ids` are present.
4. It deletes the matching vector documents from the configured storage backend.
5. If the storage backend requires a refresh after bulk operations, it refreshes the vector collection.
6. It clears the embedding cache for the affected products.

## Client creation and configuration dependency

The queue consumers do not create AI clients directly. Instead, they receive provider objects wired from `etc/di.xml`:

- `GenerateEmbeddingConsumer` receives an `EmbeddingClientProvider`.
- That provider is populated with option providers from the `Model/ClientOptions` classes.
- Those classes delegate to `Model/Config` to resolve the store-specific values for the selected embedding provider.
- The same runtime path is complemented by the markdown rendering helper `Model/Util/MarkdownProcessor`, which is used by the personalized-message service when rendering AI output for the storefront.

This makes the queue consumers depend on the same configuration model and provider adapters that the system config UI exposes.

## Queue topology

The topology file binds both message topics to dedicated queues in the Magento message broker:

```xml
<exchange name="magento" type="topic">
    <binding id="magesetu_semanticsearch_generate_embeddings"
        topic="magesetu.semanticsearch.generate_embeddings"
        destinationType="queue"
        destination="magesetu.semanticsearch.generate_embeddings" />

    <binding id="magesetu_semanticsearch_remove_embeddings"
        topic="magesetu.semanticsearch.remove_embeddings"
        destinationType="queue"
        destination="magesetu.semanticsearch.remove_embeddings" />
</exchange>
```

## Integration notes

- The consumers are intentionally defensive. They skip processing when the request is missing required data or when semantic search is disabled for the store.
- The remove consumer logs failures and returns early if vector storage operations fail.
- Dependency injection wiring in the module connects the consumers to the embedding client provider and vector storage services.

## Related feature documentation

- [Queue-based embedding processing](../features/semantic-search/queue-consumers.md)
- [Queue publishers for semantic search](../features/semantic-search/queue-publishers.md)
- [Indexer configuration](./indexer-configuration.md)
