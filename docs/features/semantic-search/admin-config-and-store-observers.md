---
type: feature
name: Admin config and store observers for semantic search
description: Documents the admin configuration-change and store-delete observers that invalidate caches, clean vector data, and reset the semantic-search indexer when settings or stores change.
tags: [magento2, magesetu, semantic-search, observer, adminhtml, configuration, cache, vector-storage]
module: MageSetu_SemanticSearch
---

# Admin config and store observers for semantic search

These adminhtml-scoped observers react when semantic-search settings change in Stores → Configuration, or when a store is deleted. Their job is to clear stale caches and vector data, and when needed invalidate the semantic-search indexer so embeddings are rebuilt for the correct model or backend.

## Overview

| Observer | Trigger | Primary reaction |
|----------|---------|------------------|
| `HandleEmbeddingConfigChange` | Section config changed; embedding LLM paths | Clean vector storage + embedding cache for inherited stores; invalidate indexer if any store cleaned successfully |
| `HandleGuardrailsConfigChange` | Section config changed; guardrails paths | Clean guardrails cache for inherited stores |
| `HandleVectorStorageConfigChange` | Section config changed; vector DB storage path | Clean embedding cache for inherited stores; invalidate indexer |
| `DeleteVectorCollectionForStore` | `store_delete_commit_after` | Clean vector storage + embedding cache for the deleted store |

All three config handlers listen to the same Magento event:

`admin_system_config_changed_section_magesetu_semantic_search`

Each handler intersects `changed_paths` with its own watched path list and returns early when none match. Event **wiring** is documented in the adminhtml configuration doc linked below.

## Shared pattern for config handlers

1. Read `changed_paths` and optional `store` from the event.
2. Intersect with `CONFIG_NEEDING_DATA_REMOVE` for that handler; exit if empty.
3. Resolve affected store IDs with `ConfigScopeResolverInterface::getInheritedStoreIds()` using path `magesetu_semantic_search/general/enabled`, website `null`, and the event’s `store` value. If a store code is present (store-scope save), only that store is returned; if it is absent, stores that still inherit the path value are resolved.
4. Run that handler’s cleanup / invalidate steps.

Reactions are intended to be safe to run more than once (admin saves can fire overlapping path sets).

## HandleEmbeddingConfigChange

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Observer/HandleEmbeddingConfigChange.php

### Purpose

When the embedding provider or model changes, existing vectors are no longer compatible with the new embedding space. This observer cleans vector collections and embedding cache for affected stores, then invalidates the semantic-search indexer so products can be re-embedded.

### Watched config paths

- `magesetu_semantic_search/embedding_llm/provider`
- `magesetu_semantic_search/embedding_llm/openai_model`
- `magesetu_semantic_search/embedding_llm/ollama_model`

### Flow

1. Exit if none of the watched paths are in `changed_paths`.
2. Resolve inherited store IDs for the enablement path.
3. For each store ID, call `CleanVectorStorageInterface::execute($storeId)` (errors increment a counter and are logged; other stores continue).
4. For each store ID, call `EmbeddingCacheService::cleanCacheForStoreId($storeId)`.
5. If at least one store cleaned without error (`$errors < count($storeIds)`), load the semantic-search indexer via `IndexerRegistry` and call `invalidate()` when it is not already invalid.

### Dependencies

- `Magento\Framework\Indexer\IndexerRegistry`
- `MageSetu\SemanticSearch\Api\CleanVectorStorageInterface`
- `MageSetu\Common\Api\ConfigScopeResolverInterface`
- `MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService`
- `MageSetu\SemanticSearch\Logger\Logger`

## HandleGuardrailsConfigChange

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Observer/HandleGuardrailsConfigChange.php

### Purpose

When guardrails provider or model settings change, cached guardrails results for affected stores must be cleared so subsequent searches use the new configuration. This observer does **not** clean vector storage or invalidate the product indexer.

### Watched config paths

- `magesetu_semantic_search/guardrails/provider`
- `magesetu_semantic_search/guardrails/openai_model`
- `magesetu_semantic_search/guardrails/ollama_model`

### Flow

1. Exit if none of the watched paths are in `changed_paths`.
2. Resolve inherited store IDs for the enablement path.
3. For each store ID, call `GuardrailsCacheService::cleanCacheForStoreId($storeId)`.

### Dependencies

- `MageSetu\Common\Api\ConfigScopeResolverInterface`
- `MageSetu\SemanticSearch\Model\Cache\Service\GuardrailsCacheService`
- `MageSetu\SemanticSearch\Logger\Logger`

## HandleVectorStorageConfigChange

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Observer/HandleVectorStorageConfigChange.php

### Purpose

When the configured vector-storage backend changes (`vectordb/storage`), embedding cache for affected stores is cleared and the semantic-search indexer is invalidated so data can be rebuilt against the newly selected backend. This observer does **not** call `CleanVectorStorageInterface` itself.

### Watched config paths

- `magesetu_semantic_search/vectordb/storage`

### Flow

1. Exit if the storage path is not in `changed_paths`.
2. Resolve inherited store IDs for the enablement path.
3. For each store ID, call `EmbeddingCacheService::cleanCacheForStoreId($storeId)`.
4. Load the semantic-search indexer and call `invalidate()` if it is not already invalid.

### Dependencies

- `Magento\Framework\Indexer\IndexerRegistry`
- `MageSetu\Common\Api\ConfigScopeResolverInterface`
- `MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService`
- `MageSetu\SemanticSearch\Logger\Logger`

## DeleteVectorCollectionForStore

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Observer/DeleteVectorCollectionForStore.php

### Purpose

When a Magento store is deleted, remove that store’s vector collection (and related embedding cache) so orphaned collections do not remain in the vector database.

### Trigger

- Magento event: `store_delete_commit_after`
- Event payload: the deleted `StoreInterface` via `getStore()`

### Guards

- Returns immediately if the store on the event is `null`.

### Flow

1. Resolve `store_id` from the deleted store.
2. Call `CleanVectorStorageInterface::execute($storeId)`.
3. On failure, log `[DeleteVectorCollectionForStore] Error while deleting vector collection` and return without clearing cache.
4. On success, call `EmbeddingCacheService::cleanCacheForStoreId($storeId)` and log an info message that the collection was deleted.

### Dependencies

- `MageSetu\SemanticSearch\Api\CleanVectorStorageInterface`
- `MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService`
- `MageSetu\SemanticSearch\Logger\Logger`

## Gotchas and developer notes

- **Different reactions by path group:** Embedding model changes wipe vectors and invalidate the indexer. Guardrails changes only clear guardrails cache. Vector-storage backend changes clear embedding cache and invalidate the indexer without an explicit vector-collection wipe in this observer.
- **Scope resolution:** `getInheritedStoreIds` short-circuits when a store code is present on the event (store-scope save): only that store is cleaned. When store is absent, it returns stores that still inherit the enablement path (excluding stores or websites with their own override). These handlers always pass website as `null`.
- **Partial vector-clean failure:** `HandleEmbeddingConfigChange` still invalidates the indexer if at least one store’s vector clean succeeded; if every store fails, the indexer is left as-is.
- **Idempotency:** Cache cleans and indexer invalidate checks (`isInvalid()`) make repeated admin saves relatively safe.

## Relationship to other docs

- [Adminhtml events and observers](../../configurations/adminhtml/events-and-observers.md) — `etc/adminhtml/events.xml` registration.
- [System configuration and defaults](../../configurations/adminhtml/system-config-and-defaults.md) — admin config tree these paths belong to.
- [Indexer flow](./indexer-flow.md) — what happens after the semantic-search indexer is invalidated.
- [Vector storage overview](./vector-storage-overview.md) — storage backends affected by clean / backend-switch flows.
- [Catalog lifecycle hooks](./catalog-lifecycle-hooks.md) — product save/delete/bulk hooks that keep embeddings in sync during normal catalog edits.
