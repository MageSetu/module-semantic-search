---
type: feature
name: Catalog lifecycle hooks for semantic search
description: Documents the product save/delete observers and mass-attribute ActionPlugin that keep semantic-search embeddings aligned with catalog changes.
tags: [magento2, magesetu, semantic-search, observer, plugin, catalog, indexer]
module: MageSetu_SemanticSearch
---

# Catalog lifecycle hooks for semantic search

These hooks keep semantic-search data in sync when catalog products are created, updated, or removed. Two observers react to Magento product lifecycle events; one plugin covers bulk attribute updates that do not fire a full product-save path for each SKU.

## Overview

| Hook | Type | Trigger | Downstream action |
|------|------|---------|-------------------|
| `ProductSaveAfter` | Observer | `catalog_product_save_after` | `Processor::reindexRow()` when the indexer is not on schedule |
| `ActionPlugin` | Plugin | `Product\Action::updateAttributes()` (after) | `Processor::reindexList()` when the indexer is not on schedule |
| `ProductDeleteAfter` | Observer | `catalog_product_delete_after_done` | `RemoveEmbeddingsPublisher` per enabled store |

Event and plugin **wiring** (XML / DI registration) lives in the configuration docs linked at the end of this page.

## ProductSaveAfter

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Observer/ProductSaveAfter.php

### Purpose

After a single product is saved, ensure the semantic-search indexer processes that product so embeddings stay current for the product’s store context.

### Trigger

- Magento event: `catalog_product_save_after`
- Event payload: the saved `Magento\Catalog\Model\Product` via `getProduct()`

### Guards

- Returns immediately if the product is missing or has no ID.
- Returns if the module is disabled for the product’s `store_id` (`ConfigInterface::isModuleEnabled`).

### Flow

1. Resolve `product_id` and `store_id` from the saved product.
2. If the semantic-search indexer is **not** in Update on Schedule mode (`Processor::isIndexerScheduled()` is false), call `Processor::reindexRow($productId)`.
3. On failure, log `[ProductSaveAfter] Error while reindexing product` and do not rethrow.

When the indexer **is** scheduled, Magento’s mview / changelog path owns reindexing; this observer deliberately skips an immediate `reindexRow` call.

### Dependencies

- `MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor`
- `MageSetu\SemanticSearch\Api\ConfigInterface`
- `MageSetu\SemanticSearch\Logger\Logger`

## ActionPlugin

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Plugin/Model/Product/ActionPlugin.php

### Purpose

Mass attribute updates (Admin → Catalog → Products → Update Attributes) change many products without relying on a per-product `catalog_product_save_after` path for reindex. This plugin reindexes the affected product IDs after Magento finishes `updateAttributes()`.

### Trigger

- Intercept: `afterUpdateAttributes` on `Magento\Catalog\Model\Product\Action`
- DI registration: `sortOrder` 10 in `etc/di.xml`

### Guards

- Returns the original result unchanged if the module is disabled for the given `$storeId`.
- Returns unchanged if `$productIds` is empty.

### Flow

1. Magento updates attributes for the given product IDs and store.
2. If the indexer is not scheduled, call `Processor::reindexList($productIds)`.
3. On failure, log `[MassAttributeUpdate] Custom indexer failed during product mass update` (includes indexer ID, product IDs, store ID) and still return `$result` so the admin mass update is not aborted.

Like `ProductSaveAfter`, scheduled mode leaves work to Magento’s indexer schedule rather than running an immediate list reindex.

### Dependencies

- `MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Processor`
- `MageSetu\SemanticSearch\Api\ConfigInterface`
- `MageSetu\SemanticSearch\Logger\Logger`

## ProductDeleteAfter

Implementation: <magento-root>/app/code/MageSetu/SemanticSearch/Observer/ProductDeleteAfter.php

### Purpose

When a product is deleted, remove its vector documents asynchronously for every store where semantic search is enabled, so orphaned embeddings do not remain in vector storage.

### Trigger

- Magento event: `catalog_product_delete_after_done`
- Event payload: the deleted product via `getProduct()`

### Guards

- Returns immediately if the product is missing or has no ID.

Unlike the save/bulk hooks, this observer does **not** check `isModuleEnabled` per product store; it publishes cleanup for each store returned by `EnabledStoresProviderInterface`.

### Flow

1. Resolve `product_id` from the deleted product.
2. Load enabled store IDs via `EnabledStoresProviderInterface::getEnabledStoreIds()`.
3. For each store, publish:

```php
[
    'store_id' => $storeId,
    'product_ids' => [$productId]
]
```

via `RemoveEmbeddingsPublisher::publish()`.
4. Per-store publish failures are logged (`[ProductDeleteAfter] Error while reindexing product`) and do not stop remaining stores.

Cleanup is asynchronous: the observer only enqueues work; consumers perform the actual vector removal.

### Dependencies

- `MageSetu\SemanticSearch\Model\Queue\RemoveEmbeddingsPublisher`
- `MageSetu\SemanticSearch\Api\EnabledStoresProviderInterface`
- `MageSetu\SemanticSearch\Logger\Logger`

## Gotchas and developer notes

- **Save vs bulk:** Single-product edits go through `ProductSaveAfter` (`reindexRow`). Mass attribute updates go through `ActionPlugin` (`reindexList`). Both skip immediate processing when the indexer is on schedule.
- **Delete path is queue-based:** Product deletion does not call `Processor` directly; it publishes to the remove-embeddings topic for each enabled store.
- **Errors are swallowed after logging:** Save, bulk, and delete hooks catch exceptions so catalog operations are not blocked by semantic-search failures.
- **Module enablement:** Save and bulk hooks respect store-scoped module enablement. Delete publishes for all stores from the enabled-stores provider.

## Relationship to other docs

- [Global events and observers](../../configurations/global/events-and-observers.md) — `etc/events.xml` registration for the product save/delete observers.
- [Global DI configuration](../../configurations/global/di-global-configuration.md) — `ActionPlugin` registration on `Product\Action`.
- [Indexer flow](./indexer-flow.md) — how `Processor` prepares and indexes product data.
- [Queue publishers for semantic search](./queue-publishers.md) — `RemoveEmbeddingsPublisher` payload and consumer hand-off.
- [Queue-based embedding processing](./queue-consumers.md) — what happens after remove-embeddings messages are consumed.
