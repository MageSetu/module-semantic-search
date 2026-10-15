---
type: feature
name: Semantic Search indexer flow
description: Documents how the custom Magento indexer for semantic search is registered, triggered, and processed for full or partial product reindexing.
tags: [magento2, magesetu, semantic-search, indexer, mview]
module: MageSetu_SemanticSearch
---

# Semantic Search Indexer Flow

The semantic search indexer is a custom Magento indexer that prepares product content for later embedding generation. It does not generate vectors by itself; instead, it collects product data, normalizes it, and stores a pending payload that downstream processing can consume.

## Overview

The flow is split into two main execution paths:

- Full reindexing, used when Magento runs a full reindex or when the indexer is explicitly rebuilt.
- Partial updating, used when product changes are detected through Magento’s mview changelog.

This design allows the module to react to product changes efficiently while still supporting bulk rebuilds.

## Registration and trigger model

The indexer is declared in <magento-root>/app/code/MageSetu/SemanticSearch/etc/indexer.xml and linked to Magento’s change tracking system in <magento-root>/app/code/MageSetu/SemanticSearch/etc/mview.xml.

### Indexer registration

Key points from <magento-root>/app/code/MageSetu/SemanticSearch/etc/indexer.xml:

- Indexer ID: `magesetu_semanticsearch_product`
- View ID: `magesetu_semanticsearch_product`
- Implementation class: `MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Indexer`
- Primary entity: `catalog_product`
- Dependencies include `catalog_product_attribute` and `catalog_product_price`

This means the semantic search indexer runs after core product-related data is available and committed.

### Mview subscriptions

The mview configuration in <magento-root>/app/code/MageSetu/SemanticSearch/etc/mview.xml subscribes the indexer to product-change events from:

- `catalog_product_entity`
- `catalog_product_entity_varchar`
- `catalog_product_entity_text`

These subscriptions allow Magento to collect product changes in the changelog and trigger the indexer only when relevant data changes.

## Execution entry points

The implementation entry point is <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Indexer.php.

The class implements both Magento indexer interfaces and delegates execution to dedicated action classes:

- `executeFull()` → used for full reindexing
- `execute($ids)` → used for partial updates from mview
- `executeList(array $ids)` → used for list-based execution
- `executeRow($id)` → used for single-row execution

## Full reindex flow

The full reindex path is handled by <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/Full.php.

### Steps

1. Load the enabled store IDs.
2. Check whether semantic attributes are configured for each store.
3. If no attributes are selected, skip indexing for that store.
4. Build product ID batches using a query generator and chunk size.
5. Load a product collection for each batch, selecting the configured attributes.
6. Pass the batch to the shared batch processor.

### Important behavior

The full flow is store-aware and uses Magento product visibility/status filtering to keep the index focused on products that are actually eligible for search.

## Partial update flow

The partial update path is handled by <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/ListAction.php.

### Steps

1. Normalize the incoming changed product IDs.
2. Resolve which websites the affected products belong to.
3. Map those websites to the enabled stores.
4. For each matching store, load the affected products and apply the same batch logic.

This makes the indexer responsive to product edits without requiring a full rebuild every time.

## Shared batch processing

Both full and partial flows eventually reach <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/AbstractAction.php.

### Batch logic

The shared processor performs the following actions:

1. Loads the selected semantic attributes from each product.
2. Strips HTML tags and whitespace from the attribute values.
3. Builds a normalized payload containing:
   - `product_id`
   - `semantic_attributes`
4. Sends the payload to the product management layer for persistence as pending work.

After those pending rows are stored, the product management layer publishes them to the asynchronous queue using `GenerateEmbeddingsPublisher`. This is the handoff point between the indexer-produced payloads and the downstream consumer that performs embedding generation. For more detail, see [Queue publishers for semantic search](./queue-publishers.md).

A simplified view of the flow looks like this:

```text
Product change/event
  → Magento mview or full reindex trigger
  → indexer action resolves affected products
  → product attributes are normalized
  → pending payloads are stored for downstream embedding work
```

## Data shape produced by the indexer

The indexer does not store embeddings directly. Instead, it prepares structured payloads that look like this conceptually:

```php
[
    'store_id' => 1,
    [
        'product_id' => 123,
        'semantic_attributes' => [
            'name' => 'Example product',
            'description' => 'A useful description'
        ]
    ]
]
```

## Notes and developer considerations

- The indexer is intentionally separated from embedding generation; it prepares data for a later asynchronous step.
- Batch sizes are configurable through the module’s configuration layer and default to a safe chunk size.
- The flow respects product status and visibility, so only eligible products are considered.
- The implementation is store-aware, which is important for multi-store Magento setups.

## Related files

- <magento-root>/app/code/MageSetu/SemanticSearch/etc/indexer.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/mview.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Indexer.php
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/Full.php
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/ListAction.php
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Action/AbstractAction.php
