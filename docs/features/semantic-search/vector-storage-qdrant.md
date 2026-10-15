---
type: feature
name: Qdrant vector storage
description: Documents the Qdrant-backed implementation of the semantic-search vector storage layer.
tags: [magento2, magesetu, semantic-search, vector-storage, qdrant]
module: MageSetu_SemanticSearch
---

# Qdrant vector storage

The Qdrant implementation provides an HTTP-based vector storage backend for semantic search. It uses the configured Qdrant endpoint and auth headers to manage collections and store embedding documents.

## Responsibilities

- Check whether a collection exists.
- Create and delete Qdrant collections for each store-specific semantic-search dataset.
- Save and bulk-save vector documents using the Qdrant REST API.
- Delete documents by product IDs and support search operations when the backend is queried.

## Key dependencies

- `Model/Vector/Storage/AbstractStorage` for collection naming.
- `Api/EnvironmentResolverInterface` for the collection prefix.
- `Api/ConfigInterface` for the Qdrant URL and authentication settings.
- `MageSetu\Common\Api\HttpClientInterface` for REST requests.

## DI configuration references

- `etc/di.xml` registers the Qdrant backend in the storage pool and injects a `SemanticSearchHttpClient` virtual type into `Model/Vector/Storage/Qdrant`.
- This makes the Qdrant implementation configurable through DI without changing the storage class itself.

## Important behavior

- The backend uses the configured Qdrant API URL and optional token from the module configuration.
- It treats bulk operations as non-refreshing, which differs from the OpenSearch implementation.
- Errors are wrapped in `VectorStorageException` and logged with detailed context.

## Related documents

- [Vector storage overview](./vector-storage-overview.md)
- [Vector storage provider and registry](./vector-storage-provider.md)
- [OpenSearch vector storage](./vector-storage-opensearch.md)
- [Environment resolver](./environment-resolver.md)
