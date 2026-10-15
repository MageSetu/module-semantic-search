---
type: feature
name: Vector storage overview
description: Documents the semantic-search vector storage abstraction, provider selection, and the storage implementations used for indexing and retrieval.
tags: [magento2, magesetu, semantic-search, vector-storage, search-backend]
module: MageSetu_SemanticSearch
---

# Vector storage overview

The semantic-search module uses a pluggable vector storage layer to persist embedding documents and serve similarity searches. The storage subsystem is split into a generic contract, a provider/registry layer, and backend-specific implementations for OpenSearch and Qdrant.

## Main layers

- The storage contract is defined by `Api/VectorStorageInterface` and implemented by the backend adapters.
- `Model/Vector/VectorStoragePool` registers the available storage backends by provider code.
- `Model/Vector/StorageProvider` resolves the configured backend and returns the active storage client.
- `Model/Vector/Storage/AbstractStorage` centralizes common collection naming behavior.

## Backend implementations

- [OpenSearch vector storage](./vector-storage-opensearch.md) handles index lifecycle, document writes, and bulk operations for the OpenSearch backend.
- [Qdrant vector storage](./vector-storage-qdrant.md) handles collection lifecycle and HTTP-based document operations for Qdrant.

## Runtime flow

1. The configured vector storage provider is resolved from the admin config value for the vector database backend.
2. The storage pool returns the matching backend implementation.
3. The storage implementation creates or uses a collection/index per store and persists embedding documents for search.
4. The environment resolver supplies the collection-prefix value from deployment config.

## Related documents

- [Environment resolver](./environment-resolver.md)
- [Queue-based embedding processing](./queue-consumers.md)
- [Configuration overview](../../configurations/global/di-global-configuration.md)
