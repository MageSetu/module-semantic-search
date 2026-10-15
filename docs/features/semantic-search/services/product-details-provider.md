---
type: feature
name: Product details provider service
description: Documents the service that builds lightweight product result payloads for semantic-search responses.
tags: [magento2, magesetu, semantic-search, service, product-details]
module: MageSetu_SemanticSearch
---

# Product Details Provider Service

The product details provider service assembles the product metadata shown in semantic-search results, such as name, short description, image, price, and link.

## Responsibilities

- Load Magento products by ID for a specific store.
- Filter the products to enabled and visible catalog items.
- Build a result DTO suitable for the front-end search response.

## Main flow

1. The service receives product IDs and a store ID.
2. It loads a filtered product collection.
3. It maps each product into a search-result DTO.
4. It preserves the original order of the input IDs.

## Key implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Service/ProductDetailsProvider.php

## Notes

This service is focused on presentation data for search results rather than the indexing or embedding logic itself.
