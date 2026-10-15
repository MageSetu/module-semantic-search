---
type: feature
name: Queue publishers for semantic search
description: Documents the message queue publisher classes that hand off indexer and observer work to the asynchronous embedding consumers.
tags: [magento2, magesetu, semantic-search, queue, publisher, async-processing]
module: MageSetu_SemanticSearch
---

# Queue publishers for semantic search

The semantic search module uses dedicated publisher classes to send work into Magento’s message queue after data has been prepared by the indexer or by product lifecycle observers.

## Overview

Two publisher classes are involved in the asynchronous flow:

- `GenerateEmbeddingsPublisher` publishes embedding-generation jobs for products that were prepared by the indexer.
- `RemoveEmbeddingsPublisher` publishes cleanup jobs for products that were deleted or otherwise removed from the semantic-search pipeline.

## GenerateEmbeddingsPublisher

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Model/Queue/GenerateEmbeddingsPublisher.php

### Responsibility

This publisher sends a serialized payload to the `magesetu.semanticsearch.generate_embeddings` topic.

### Payload shape

```php
[
    'store_id' => 1,
    'products' => [
        [
            'product_id' => 123,
            'semantic_attributes' => []
        ]
    ]
]
```

### Usage

The publisher is used by the product management layer after pending semantic-search records are persisted. This is the point where the indexer’s prepared product data becomes an async embedding job.

#### Primary call site

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/ProductManagement.php

This class builds the message payload from the pending rows prepared by the indexer flow and publishes it to the queue.

## RemoveEmbeddingsPublisher

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Model/Queue/RemoveEmbeddingsPublisher.php

### Responsibility

This publisher sends a serialized payload to the `magesetu.semanticsearch.remove_embeddings` topic.

### Payload shape

```php
[
    'store_id' => 1,
    'product_ids' => [101, 102, 103]
]
```

### Usage

The publisher is used by the product delete observer to trigger vector-document cleanup once a product is removed from the catalog.

#### Primary call site

- <magento-root>/app/code/MageSetu/SemanticSearch/Observer/ProductDeleteAfter.php

The observer gathers the affected product ID and publishes a removal job for each enabled store.

## Relationship to other docs

- [Queue consumer configuration](../../configurations/queue-consumer-configuration.md) describes the consumers that receive these messages.
- [Indexer flow](./indexer-flow.md) shows where the indexer prepares pending product payloads that later become queue messages.
- [Queue-based embedding processing](./queue-consumers.md) explains what the consumers do after receiving the payload.

## Notes

These publishers are small orchestration classes. They do not perform embedding generation or vector deletion themselves; they simply hand off the prepared payload to Magento’s queue infrastructure so the consumers can process it asynchronously.
