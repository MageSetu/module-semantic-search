---
type: configuration
name: Global events and observers
description: Documents the global Magento events and observers registered in etc/events.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, global, events, observers]
module: MageSetu_SemanticSearch
---

# Overview

The global event wiring for this module is defined in etc/events.xml. These listeners are active in the global scope and help keep semantic-search data aligned with catalog lifecycle changes.

## Registered events

### catalog_product_save_after

- Observer: MageSetu\SemanticSearch\Observer\ProductSaveAfter
- Purpose: reacts when a product is saved and triggers the update path for semantic-search indexing or embedding refresh.
- Event payload: Magento provides the saved product instance through the event object.

### catalog_product_delete_after_done

- Observer: MageSetu\SemanticSearch\Observer\ProductDeleteAfter
- Purpose: removes or cleans up semantic-search vectors or embeddings when a product is deleted.
- Event payload: Magento provides the deleted product through the event object.

## Observer responsibilities

- ProductSaveAfter: handles product-save updates and ensures the semantic-search representation stays current.
- ProductDeleteAfter: performs cleanup after product removal so stale embeddings do not remain in storage.

## Example configuration

```xml
<event name="catalog_product_save_after">
    <observer name="magesetu_semanticsearch_product_index_row"
        instance="MageSetu\SemanticSearch\Observer\ProductSaveAfter" />
</event>
```

## Integration notes

- These listeners are global and are loaded for the core application scope.
- They are intended to be lightweight and idempotent because they run on catalog lifecycle operations.
- The observers depend on the product object passed by Magento’s product event pipeline.
