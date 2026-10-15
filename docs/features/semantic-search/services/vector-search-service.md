---
type: feature
name: Vector search service
description: Documents the service that transforms a user query into an embedding and searches the vector store for matching products.
tags: [magento2, magesetu, semantic-search, service, search]
module: MageSetu_SemanticSearch
---

# Vector Search Service

The vector search service is the query-time component that turns a user search phrase into an embedding and uses the configured vector storage to fetch matching products.

## Responsibilities

- Check the cache for a previously computed result.
- Generate an embedding for the incoming query.
- Search the configured vector database for similar product vectors.
- Return the matching product IDs for downstream product-detail rendering.

## Main flow

1. The service receives a user query and store ID.
2. It tries the cache first.
3. If missing, it creates an embedding for the query.
4. It submits the embedding to the vector storage search API.
5. It returns the matching product IDs.

## Key implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Service/VectorSearchService.php

## Notes

This service is intentionally lightweight and depends on the configured embedding provider and vector storage to perform the actual search work.
