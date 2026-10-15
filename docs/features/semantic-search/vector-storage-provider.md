---
type: feature
name: Vector storage provider and registry
description: Documents the provider and registry classes that select and expose the active semantic-search vector storage backend.
tags: [magento2, magesetu, semantic-search, vector-storage, provider, registry]
module: MageSetu_SemanticSearch
---

# Vector storage provider and registry

This document covers the registry and provider classes that select the active vector storage backend used by semantic search.

## Components

### VectorStoragePool

`Model/Vector/VectorStoragePool` is the registry that holds the available storage implementations keyed by provider code such as `opensearch` and `qdrant`. It exposes the available backend codes and returns the matching implementation instance when requested.

### StorageProvider

`Model/Vector/StorageProvider` is the runtime entry point used by embedding and search services. It reads the configured vector storage provider from the module config, asks the pool for the matching backend, and returns a storage client instance.

## DI wiring

The pool and provider are wired in `etc/di.xml` so that the module can swap storage implementations without changing service classes. The relevant registrations are:

- `Api/VectorStoragePoolInterface` -> `Model/Vector/VectorStoragePool`
- `Api/VectorStorageProviderInterface` -> `Model/Vector/StorageProvider`
- `VectorStoragePool` argument `storages` contains the `opensearch` and `qdrant` backend implementations

The vector storage provider is then consumed by the embedding generator, the queue consumers, and the search services.

## Relationship to other docs

- [Vector storage overview](./vector-storage-overview.md)
- [OpenSearch vector storage](./vector-storage-opensearch.md)
- [Qdrant vector storage](./vector-storage-qdrant.md)
- [Environment resolver](./environment-resolver.md)
