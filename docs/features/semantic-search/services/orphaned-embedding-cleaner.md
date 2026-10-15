---
type: feature
name: Orphaned embedding cleaner service
description: Documents the maintenance service that removes vector documents which no longer correspond to a valid Magento product.
tags: [magento2, magesetu, semantic-search, service, maintenance]
module: MageSetu_SemanticSearch
---

# Orphaned Embedding Cleaner Service

The orphaned embedding cleaner service removes vector documents for products that no longer exist in Magento or are no longer associated with the relevant website.

## Responsibilities

- Inspect vector documents for a store.
- Compare them against Magento’s product-website associations.
- Identify orphaned product IDs.
- Remove the stale documents optionally in dry-run mode.

## Main flow

1. Resolve the vector storage client for the store.
2. Load batched product IDs from the vector store.
3. Compare them with Magento product IDs for the store website.
4. Delete any missing records or report them in dry-run mode.

## Key implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Service/OrphanedEmbeddingCleaner.php

## Notes

This service is useful for data hygiene and helps keep the vector store aligned with Magento’s product catalog.
