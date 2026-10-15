---
type: feature
name: Queue-based embedding processing
description: Explains the asynchronous embedding generation and removal workflow executed by the semantic search queue consumers.
tags: [magento2, magesetu, semantic-search, queue, embeddings, async-processing]
module: MageSetu_SemanticSearch
---

# Queue-based embedding processing

The semantic search module uses Magento message queues to perform embedding-related work asynchronously so that product updates and removals do not block the main request flow.

## Overview

Two queue consumers are responsible for the background work:

- The generate consumer creates embeddings for products that need indexing.
- The remove consumer deletes embeddings and clears related cache entries when products are removed or invalidated.

## Generate embeddings flow

1. A serialized message containing a `store_id` and a product payload is placed on the `magesetu.semanticsearch.generate_embeddings` queue.
2. The `GenerateEmbeddingConsumer` validates the request and checks that semantic search is enabled for the store.
3. The consumer delegates to the embedding generator service to produce the vector representations needed by the search layer.

## Remove embeddings flow

1. A serialized message containing a `store_id` and `product_ids` is placed on the `magesetu.semanticsearch.remove_embeddings` queue.
2. The `RemoveEmbeddingConsumer` validates the request and checks the module state for the store.
3. It deletes the related documents from the configured vector storage backend.
4. It refreshes the vector collection when required and clears the embedding cache for the affected products.

## Relationship to configuration

The queue wiring and consumer registration are documented in [Queue consumer configuration](../../configurations/queue-consumer-configuration.md). The upstream publish step is documented in [Queue publishers for semantic search](./queue-publishers.md). The embedding pipeline also depends on the environment resolver and storage configuration layer for collection naming and backend selection, as described in [Vector storage overview](./vector-storage-overview.md).

## Notes

This flow is intentionally asynchronous and defensive. It prevents the storefront or admin request from waiting on external embedding work while still ensuring that vector data stays consistent with product changes.
