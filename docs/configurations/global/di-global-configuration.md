---
type: configuration
name: Global DI configuration
description: Documents the global dependency injection configuration present in etc/di.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, global, di]
module: MageSetu_SemanticSearch
---

# Overview

The global DI configuration is defined in etc/di.xml and contains the module’s core object-manager wiring. This file registers the main implementation classes, injects runtime dependencies, and defines the AI and vector-storage services used by semantic search.

## Configuration Changes and Classes Wired

### Preferences

The following interface preferences are registered in the global DI configuration:

- MageSetu\SemanticSearch\Api\CleanVectorStorageInterface -> MageSetu\SemanticSearch\Model\Vector\CleanVectorStorage
- MageSetu\SemanticSearch\Api\ConfigInterface -> MageSetu\SemanticSearch\Model\Config
- MageSetu\SemanticSearch\Api\EnabledStoresProviderInterface -> MageSetu\SemanticSearch\Model\EnabledStoresProvider
- MageSetu\SemanticSearch\Api\EnvironmentResolverInterface -> MageSetu\SemanticSearch\Model\EnvironmentResolver
- MageSetu\SemanticSearch\Api\ProductManagementInterface -> MageSetu\SemanticSearch\Model\ProductManagement
- MageSetu\SemanticSearch\Api\SemanticSearchInterface -> MageSetu\SemanticSearch\Model\SemanticSearch
- MageSetu\SemanticSearch\Api\VectorStoragePoolInterface -> MageSetu\SemanticSearch\Model\Vector\VectorStoragePool
- MageSetu\SemanticSearch\Api\VectorStorageProviderInterface -> MageSetu\SemanticSearch\Model\Vector\StorageProvider
- MageSetu\SemanticSearch\Api\Data\ProductInterface -> MageSetu\SemanticSearch\Model\Product
- MageSetu\SemanticSearch\Api\Data\SearchResultProductInterface -> MageSetu\SemanticSearch\Model\Data\SearchResultProduct
- MageSetu\SemanticSearch\Api\Data\SemanticSearchResponseInterface -> MageSetu\SemanticSearch\Model\Data\SemanticSearchResponse
- MageSetu\SemanticSearch\Api\Data\VectorDocumentInterface -> MageSetu\SemanticSearch\Model\Vector\Document

### Logger Configuration

A custom logger is created for the module using:

- MageSetu\SemanticSearch\Logger\Logger

It uses a custom handler:

- MageSetu\SemanticSearch\Logger\ErrorHandler

The handler writes to /var/log/magesetu_semantic_search.log.

### Console Commands

The module registers two console commands in Magento’s command list:

- MageSetu\SemanticSearch\Console\Command\RemoveOrphanedEmbeddingsCommand
- MageSetu\SemanticSearch\Console\Command\ShowCollectionPrefixCommand

### Plugin Configuration

A plugin is attached to Magento\Catalog\Model\Product\Action:

- MageSetu\SemanticSearch\Plugin\Model\Product\ActionPlugin

This plugin is registered with sortOrder 10.

### UI Grid Data Source Configuration

The global DI file also registers the admin UI grid collection for semantic search products:

- MageSetu\SemanticSearch\Model\ResourceModel\Product\Grid\Collection

This is used through the collection factory mapping for the data source named magesetu_semanticsearch_products_listing_data_source.

### AI Client Pool and Provider Configuration

The module defines several virtual types for AI integration:

- EmbeddingClientPool -> MageSetu\AiBase\Model\Client\AiClientPool
- PersonalizedMessageClientPool -> MageSetu\AiBase\Model\Client\AiClientPool
- GuardrailsClientPool -> MageSetu\AiBase\Model\Client\AiClientPool

These pools are then used by provider virtual types:

- EmbeddingClientProvider -> MageSetu\AiBase\Model\Client\AiClientProvider
- PersonalizedMessageClientProvider -> MageSetu\AiBase\Model\Client\AiClientProvider
- GuardrailsClientProvider -> MageSetu\AiBase\Model\Client\AiClientProvider

The pools are configured with provider factories for OpenAI and Ollama, and the providers receive the corresponding option provider classes from the module.

The same pools are injected into the admin connection-test controllers so each **Test Connection** button validates the correct capability:

- `TestEmbeddingConnection` → `EmbeddingClientPool`
- `TestPersonalizedMessageConnection` → `PersonalizedMessageClientPool`
- `TestGuardrailsConnection` → `GuardrailsClientPool`

See [Admin system configuration test connection buttons](../adminhtml/system-config-test-connections.md).

### Configuration model and client option providers

In addition to the AI client pools, the module wires configuration access and client-option adapters through DI:

- `MageSetu\SemanticSearch\Model\Config` implements `ConfigInterface` and reads the `magesetu_semantic_search` config tree for module state, AI model settings, guardrail options, and vector database endpoints.
- The `Model\ClientOptions` classes provide provider-specific option arrays for the AI clients. The DI wiring injects `OpenAiEmbeddingOptions` and `OllamaEmbeddingOptions` into `EmbeddingClientProvider`, the personalized-message variants into `PersonalizedMessageClientProvider`, and the guardrails variants into `GuardrailsClientProvider`.
- `PayloadProcessor` also receives the embedding option provider list through `embeddingClientOptionsProviders`, allowing queue-driven embedding generation to reuse the same provider mapping.
- `Model\Config\Source\AiSearchContextAttributeProvider` and `Model\Config\Source\VectorStorage` are used by the admin configuration UI to populate source-model options for semantic attributes and vector storage selection.
- `Model\EnvironmentResolver` is injected into the vector storage layer through `EnvironmentResolverInterface` to supply the collection prefix from deployment config.

### Service Injection Configuration

The following services receive their dependencies through DI:

- MageSetu\SemanticSearch\Model\Service\PersonalizedMessageService
- MageSetu\SemanticSearch\Model\Queue\GenerateEmbeddingConsumer
- MageSetu\SemanticSearch\Model\Service\VectorSearchService
- MageSetu\SemanticSearch\Model\Service\SearchQueryValidator
- MageSetu\SemanticSearch\Model\Service\PayloadProcessor

### Vector Storage Configuration

The vector storage system is wired through the global DI configuration:

- MageSetu\SemanticSearch\Model\Vector\VectorStoragePool
- MageSetu\SemanticSearch\Model\Vector\Storage\OpenSearch
- MageSetu\SemanticSearch\Model\Vector\Storage\Qdrant

The Qdrant storage receives the semantic search HTTP client virtual type SemanticSearchHttpClient.

## Configuration-driven runtime classes

The DI wiring in etc/di.xml does more than connect interfaces to implementations. It also links Magento’s configuration tree to the runtime services that consume it:

- `Model/Config` implements `ConfigInterface` and is the central accessor for the `magesetu_semantic_search` configuration section. It resolves store-scoped values for module state, provider selection, cache lifetime, and vector database settings.
- `Model/ClientOptions` contains provider classes such as `OpenAiEmbeddingOptions`, `OllamaEmbeddingOptions`, `OpenAiGuardrailsOptions`, `OllamaGuardrailsOptions`, `OpenAiPersonalizedMessageOptions`, and `OllamaPersonalizedMessageOptions`. These classes adapt the chosen provider into the option arrays expected by the AI client providers in the DI container.

This makes the configuration docs and the DI docs complementary: the system config UI defines the values, while `Model/Config` and `Model/ClientOptions` make those values available to the embedding, guardrails, and personalized-message services.

## Notes

This file is the central dependency-injection definition for the module. It wires the module’s interfaces to concrete implementations, registers commands and plugins, and provides the AI and vector-storage infrastructure needed by semantic search.
