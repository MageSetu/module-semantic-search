---
type: configuration
name: System configuration and defaults
description: Documents the admin system configuration section in etc/adminhtml/system.xml and the default values in etc/config.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, system-config, defaults]
module: MageSetu_SemanticSearch
---

# Overview

This document covers the module’s admin configuration UI and its default configuration values. The admin-facing settings are defined in etc/adminhtml/system.xml, while the default values that populate the config tree are defined in etc/config.xml.

## System configuration section

The module registers a configuration section with the following identity:

- Section ID: magesetu_semantic_search
- Tab: magesetu_common
- Resource: MageSetu_SemanticSearch::config
- Visibility: shown in default, website, and store scopes

## Main configuration groups

### General settings

Contains core module options such as:

- Enable Module
- Semantic Attributes
- Indexer Batch Size
- Embedding Batch Size

These settings control whether the module is active and how indexing and embedding work.

### Embedding LLM settings

Contains embedding-provider settings for the AI model used to generate embeddings, including:

- Embedding Provider
- OpenAI model and credentials
- Ollama model and credentials
- Embedding cache lifetime
- Test Connection button (`test_embedding_connection`)

### Guardrails settings

Contains query guardrail behavior and provider selection, including:

- Enable Query AI Guardrails
- Guardrails Provider
- OpenAI or Ollama guardrail model settings
- Test Connection button (`test_connection`)
- Guardrails system prompt
- Guardrails error message
- Cache lifetime

### Vector database settings

Contains vector-store selection and related connection settings, including:

- Vector Storage Provider
- OpenSearch settings
- Test OpenSearch Connection button (`test_opensearch_connection`)
- Qdrant settings
- Test Qdrant Connection button (`test_qdrant_connection`)
- Search Limit
- Score Threshold

### Personalized message settings

Contains settings for the optional personalized-message feature, including:

- Enable Personalized Message
- Provider selection
- OpenAI or Ollama provider settings
- Test Connection button (`test_connection`)

## Test connection buttons

Five admin buttons validate live form values against external services without saving configuration first:

1. Embedding provider — `embedding_llm/test_embedding_connection`
2. Personalized message — `personalized_message/test_connection`
3. Guardrails — `guardrails/test_connection`
4. OpenSearch — `vectordb/test_opensearch_connection`
5. Qdrant — `vectordb/test_qdrant_connection`

Each button uses a frontend model, RequireJS AJAX widget, and admin controller under `magesetu_semanticsearch/systemConfig/*`. Full field mappings, response contracts, and DI pool wiring are documented in [system-config-test-connections.md](system-config-test-connections.md).

## Default configuration values

The module’s default values are declared in etc/config.xml under the magesetu_semantic_search node. Key defaults include:

- General
  - enabled = 0
  - semantic_attributes = name,ai_search_context,short_description,description ([attribute installed by data patch](../../database-schema/ai-search-context-product-attribute.md))
  - index_batch_size = 1000
  - embedding_batch_size = 100
- Personalized message
  - enabled = 0
  - ollama_base_url = http://localhost:11434
- Embedding LLM
  - ollama_base_url = http://localhost:11434
  - cache_lifetime = 86400
- Guardrails
  - enabled = 0
  - ollama_base_url = http://localhost:11434
  - reasoning_effort = none
  - max_tokens = 128
  - cache_lifetime = 86400
- Vector database
  - storage = opensearch
  - opensearch_server_timeout = 15
  - search_limit = 10
  - score_threshold = 0.65

## Runtime consumption of these settings

The values exposed in this admin configuration section are not only used by the UI. They are consumed by `Model/Config`, which centralizes access to the `magesetu_semantic_search` tree, and by the `Model/ClientOptions` provider classes, which translate those values into the option arrays required by the AI client providers created in `etc/di.xml`.

The config UI also uses source models that are relevant to this configuration surface:

- `Model/Config/Source/AiSearchContextAttributeProvider` provides the attribute-selection options for the AI search-context field.
- `Model/Config/Source/VectorStorage` provides the selectable vector storage backend options exposed in the admin form.

## Integration notes

- The admin config section is protected by the ACL resource MageSetu_SemanticSearch::config.
- Several fields are conditional and only appear based on the selected provider or module state.
- The default values are used when no scope-specific override exists in Magento’s configuration system.
- Connection test buttons are declared in `system.xml` but execute through dedicated admin controllers; see [system-config-test-connections.md](system-config-test-connections.md).

## Related Documents

- [Admin system configuration test connection buttons](system-config-test-connections.md)
- [Adminhtml routes](routes.md)
- [DI configuration overview](../di-configuration-overview.md)
