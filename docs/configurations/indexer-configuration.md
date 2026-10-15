---
type: configuration
name: Semantic Search indexer configuration
description: Documents the Magento indexer and mview configuration that wire semantic search product changes into the custom indexer pipeline.
tags: [magento2, magesetu, semantic-search, indexer, mview, configuration]
module: MageSetu_SemanticSearch
---

# Semantic Search Indexer Configuration

This document captures the configuration layer that registers the semantic search indexer in Magento and connects it to product-change tracking.

## Scope

The configuration is defined in:

- <magento-root>/app/code/MageSetu/SemanticSearch/etc/indexer.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/mview.xml

## Indexer registration

The custom indexer is registered with the ID `magesetu_semanticsearch_product` and is mapped to the implementation class `MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Indexer`.

### Key settings

- Indexer ID: `magesetu_semanticsearch_product`
- View ID: `magesetu_semanticsearch_product`
- Primary entity: `catalog_product`
- Dependencies: `catalog_product_attribute`, `catalog_product_price`

These settings ensure the semantic search indexer runs after the core product attribute and price data has been processed.

## Mview subscriptions

The module subscribes to relevant product attribute tables so Magento can detect product changes through the changelog mechanism.

### Subscribed tables

- `catalog_product_entity`
- `catalog_product_entity_varchar`
- `catalog_product_entity_text`

This makes the indexer respond to product create, update, and attribute-change events without requiring a full manual reindex every time.

## Relationship to feature documents

- [Indexer flow](../features/semantic-search/indexer-flow.md) explains how the configuration triggers the indexer execution paths.
- [Product data model](../features/semantic-search//product-data-model.md) documents the data layer that stores semantic-search state for products.

## Runtime configuration references

The indexer sets up the data that later becomes embeddings, but the downstream embedding pipeline depends on runtime configuration access from `Model/Config` and provider-specific option adapters from `Model/ClientOptions`. Those classes consume the store-scoped values configured through the admin system section and make them available to the queue-driven embedding services.

## Implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/etc/indexer.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/etc/mview.xml
- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Indexer/SemanticSearch/Indexer.php
