---
type: feature
name: Embedding generator service
description: Documents the service that turns prepared semantic attributes into embeddings and saves them into the configured vector storage.
tags: [magento2, magesetu, semantic-search, service, embeddings]
module: MageSetu_SemanticSearch
---

# Embedding Generator Service

The embedding generator service is responsible for converting product semantic context into embeddings and persisting the resulting vectors to the configured vector database.

## Responsibilities

- Resolve the configured embedding client and vector storage for a store.
- Build embedding input from the semantic attributes prepared by the indexer flow.
- Use a per-store in-memory cache of attribute labels so the service does not perform repeated SQL lookups for each product.
- Process products in chunks to keep memory usage bounded.
- Save vector documents with metadata and update the semantic-search status records.

## Main flow

1. The service receives a batch of products with their semantic attributes.
2. It resolves the embedding client using the store-specific configuration.
3. It builds embedding contexts for each product, reusing cached attribute labels for the configured semantic attributes when available.
4. It sends the batch to the embedding client and receives embeddings.
5. It writes vector documents and marks the corresponding products as succeeded or failed.

## Local label-cache optimization

To reduce overhead during embedding generation, the service keeps an in-memory cache of semantic attribute labels per store. On the first context build for a store, it loads the labels for the configured semantic attributes once and reuses them for the rest of the current processing cycle. This avoids repeated database lookups for attribute labels while building the prompt text for each product and improves throughput for larger batches.

If a label is not available for a given attribute, the service falls back to a humanized version of the attribute code, such as converting underscores or hyphens to spaces.

## Key implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Service/EmbeddingGenerator.php

## Notes

The service is designed to be batch-oriented and failure-tolerant. A chunk failure does not stop the next chunk from being processed, and status updates are attempted for every processed batch.
