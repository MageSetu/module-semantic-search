---
type: feature
name: OpenSearch vector storage
description: Documents the OpenSearch-backed implementation of the semantic-search vector storage layer.
tags: [magento2, magesetu, semantic-search, vector-storage, opensearch]
module: MageSetu_SemanticSearch
---

# OpenSearch vector storage

The OpenSearch implementation is the default vector backend for the semantic-search module. It wraps the OpenSearch PHP client and provides collection lifecycle and document-CRUD operations for embedding data.

## Responsibilities

- Create, check, and delete vector indexes for each store-specific collection.
- Save and bulk-save embedding documents into the OpenSearch index.
- Delete documents by product IDs and search by vector similarity when required.
- Build store-aware collection names using the environment resolver prefix.

## Key dependencies

- `Model/Vector/Storage/AbstractStorage` for collection naming.
- `Model/Search/OpenSearchClientProvider` for client creation.
- `Api/EnvironmentResolverInterface` for the collection prefix.
- `Api/Data/VectorDocumentInterfaceFactory` for document object creation.

## DI configuration references

- The storage backends are registered in `etc/di.xml` through the `VectorStoragePool` type definition, which wires the `opensearch` and `qdrant` implementations into the runtime pool.
- The OpenSearch implementation is selected through the same pool, while its client creation flow is still handled by `Model/Search/OpenSearchClientProvider`.

## Important behavior

- The backend uses the configured `knnEngine`, `spaceType`, `efConstruction`, and `m` values for index mapping.
- It treats bulk operations as refresh-required, which means downstream flows may trigger a refresh after large writes.
- Errors are wrapped in `VectorStorageException` so callers can handle storage failures consistently.

## Related documents

- [Vector storage overview](./vector-storage-overview.md)
- [Vector storage provider and registry](./vector-storage-provider.md)
- [Qdrant vector storage](./vector-storage-qdrant.md)
- [Environment resolver](./environment-resolver.md)
