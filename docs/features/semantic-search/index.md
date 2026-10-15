---
type: feature
name: Semantic Search documentation index
description: Entry point for the semantic search documentation set, starting with the indexer flow and related implementation references.
tags: [magento2, magesetu, semantic-search, documentation, indexer]
module: MageSetu_SemanticSearch
---

# Semantic Search Documentation

## Current topics

- [System configuration and defaults](../../configurations/adminhtml/system-config-and-defaults.md) — documents the admin config section and default values.
- [Admin system configuration test connection buttons](../../configurations/adminhtml/system-config-test-connections.md) — documents the five Stores > Configuration connection-test buttons and AJAX controllers.
- [Indexer configuration](../../configurations/indexer-configuration.md) — documents how Magento registers the semantic search indexer and watches product changes.
- [Queue consumer configuration](../../configurations/queue-consumer-configuration.md) — documents the message queue consumers, topics, and topology bindings for asynchronous embedding work.
- [DI configuration overview](../../configurations/di-configuration-overview.md) — explains the global DI wiring for commands, UI providers, and service bindings.
- [Console commands](./console-commands.md) — documents the maintenance commands for orphan cleanup and collection-prefix inspection.
- [Admin semantic-search product grid](./admin-product-grid.md) — documents the backend grid for product embedding metadata and bulk actions.
- [Storefront search experience](./frontend-search-experience.md) — documents the frontend search UI, routing, Knockout components, and query controller.
- [Indexer flow](./indexer-flow.md) — explains how the custom Magento indexer is registered, triggered, and processed for product updates.
- [Queue-based embedding processing](./queue-consumers.md) — explains the asynchronous embedding generation and removal workflow executed by the queue consumers.
- [Queue publishers for semantic search](./queue-publishers.md) — documents the publisher classes that push indexer and observer work into the message queue.
- [Catalog lifecycle hooks](./catalog-lifecycle-hooks.md) — documents product save/delete observers and the mass-attribute ActionPlugin that keep embeddings aligned with catalog changes.
- [Admin config and store observers](./admin-config-and-store-observers.md) — documents config-change and store-delete observers that clean caches/vectors and invalidate the indexer.
- [Product data model](./product-data-model.md) — documents the data layer used to track semantic-search state for products.
- [Environment resolver](./environment-resolver.md) — documents the deployment-config based resolver used by the vector storage layer.
- [Vector storage overview](./vector-storage-overview.md) — documents the storage abstraction, provider layer, and backend selection flow.
- [Vector storage provider and registry](./vector-storage-provider.md) — documents the pool/provider classes that select the active backend.
- [OpenSearch vector storage](./vector-storage-opensearch.md) — documents the OpenSearch implementation.
- [Qdrant vector storage](./vector-storage-qdrant.md) — documents the Qdrant implementation.
- [Services](./services/index.md) — provides entry points for the semantic-search service classes.
- [Vector storage exceptions](../../utilities/vector-storage-exceptions.md) — documents `VectorStorageException` and `VectorStorageNotFoundException`.
- [ai_search_context product attribute data patch](../../database-schema/ai-search-context-product-attribute.md) — documents the EAV data patch that installs the store-scoped AI search context attribute.

## Implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/etc/indexer.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/mview.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Indexer.php
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/Full.php
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/ListAction.php
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/AbstractAction.php

## Notes

This documentation is intentionally scoped to the current topic. Later sections will expand into queue processing, embedding generation, vector storage, and search execution.
