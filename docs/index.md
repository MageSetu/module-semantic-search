---
type: hub
name: "MageSetu_SemanticSearch Module Documentation"
description: "Overview of the MageSetu_SemanticSearch Magento module, architecture, configuration, and CLI commands."
tags: [magento2, magesetu, ai, search, embedding, vector, opensearch]
module: "MageSetu_SemanticSearch"
---

# MageSetu_SemanticSearch Documentation

## Module Overview

`MageSetu_SemanticSearch` implements a parallel semantic search system alongside Magento’s native catalog search. It generates vector embeddings for catalog products asynchronously using AI models and queries a vector database (such as OpenSearch) for nearest matches to user search queries.

## System Requirements

- `PHP` `>= 8.4`
- `magento/framework` `>= 2.4.8`
- `magesetu/module-ai-base` `>= 1.0.0`
- `magesetu/module-common` `>= 1.0.0`

## Documentation Contents

### Configuration

- [System configuration and defaults](configurations/adminhtml/system-config-and-defaults.md)
- [Admin system configuration test connection buttons](configurations/adminhtml/system-config-test-connections.md)
- [Vector Storage Prefix Configuration](configurations/vector-storage-collection-prefix.md)

### CLI Features

- [CLI Commands](features/semantic-search/console-commands.md)

### Semantic Search Features

- [Semantic Search documentation index](features/semantic-search/index.md)
- [Catalog lifecycle hooks](features/semantic-search/catalog-lifecycle-hooks.md)
- [Admin config and store observers](features/semantic-search/admin-config-and-store-observers.md)

### Database Schema

- [Semantic search product table](database-schema/magesetu-semantic-search-product-table.md)
- [ai_search_context product attribute data patch](database-schema/ai-search-context-product-attribute.md)

### Utilities

- [Vector storage exceptions](utilities/vector-storage-exceptions.md)
