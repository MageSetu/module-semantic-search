---
type: configuration
name: Admin system configuration test connection buttons
description: Documents the five Stores > Configuration test-connection buttons for embedding, personalized message, guardrails, OpenSearch, and Qdrant, including frontend models, AJAX controllers, field mappings, and DI pool wiring.
tags: [magento2, magesetu, semantic-search, system-config, test-connection, adminhtml]
module: MageSetu_SemanticSearch
---

# Overview / Purpose

The admin configuration section `magesetu_semantic_search` exposes five **Test Connection** buttons so merchants can validate AI providers and vector-database endpoints before saving or enabling those features. Each button is a `system.xml` field of type `button` with a dedicated frontend model, RequireJS widget, and admin POST controller. Success or failure is returned as JSON and reflected on the button label without reloading the page.

These tests use the **unsaved form values** currently visible in the admin UI (via HTML field IDs), not necessarily the last-saved Magento config.

## Buttons at a glance

| Config group | Field ID | Label | Frontend model | Admin action |
|---|---|---|---|---|
| `embedding_llm` | `test_embedding_connection` | Test Connection | `Block\Adminhtml\System\Config\Embedding\TestConnection` | `systemConfig/testEmbeddingConnection` |
| `personalized_message` | `test_connection` | Test Connection | `Block\Adminhtml\System\Config\PersonalizedMessage\TestConnection` | `systemConfig/testPersonalizedMessageConnection` |
| `guardrails` | `test_connection` | Test Connection | `Block\Adminhtml\System\Config\Guardrails\TestConnection` | `systemConfig/testGuardrailsConnection` |
| `vectordb` | `test_opensearch_connection` | Test OpenSearch Connection | `Block\Adminhtml\System\Config\VectorStorage\TestOpenSearchConnection` | `systemConfig/testOpenSearchConnection` |
| `vectordb` | `test_qdrant_connection` | Test Qdrant Connection | `Block\Adminhtml\System\Config\VectorStorage\TestQdrantConnection` | `systemConfig/testQdrantConnection` |

All actions are under the admin front name `magesetu_semanticsearch` and require ACL resource `MageSetu_SemanticSearch::config`.

## Visibility (`depends`)

- **Embedding** — shown when `embedding_llm/provider` is not empty (`provider` with `negative="1"`).
- **Personalized message** — shown when personalized message is enabled and `provider` is not empty.
- **Guardrails** — shown when guardrails are enabled and `provider` is not empty.
- **OpenSearch** — shown when `vectordb/storage` is `opensearch` (default scope only: `showInWebsite="0"` / `showInStore="0"`).
- **Qdrant** — shown when `vectordb/storage` is `qdrant` (default scope only).

None of the test fields support `canRestore`.

# Technical Specifications

## Shared frontend-model base

All five frontend models extend `Block\Adminhtml\System\Config\AbstractTestConnection`, which:

- Extends `Magento\Config\Block\System\Config\Form\Field`.
- Strips scope inherit UI (`unsScope`, `unsCanUseWebsiteValue`, `unsCanUseDefaultValue`) so the button is not treated as a scoped config value.
- Renders via a PHTML template and injects `button_label`, `html_id`, `ajax_url`, and JSON `field_mapping`.
- Requires subclasses to implement `getPostUrl()` and `getFieldMapping()`.

Default template: `view/adminhtml/templates/system/config/test_connection.phtml`  
Vector-storage buttons override to: `view/adminhtml/templates/system/config/vectorstorage/test_connection.phtml`

## RequireJS widgets

Registered in `view/adminhtml/requirejs-config.js`:

| Widget alias | Script | Used by |
|---|---|---|
| `testSemanticSearchConnection` | `js/test-connection.js` | Embedding, personalized message, guardrails |
| `testVectorStorageConnection` | `js/vectorstorage/test-connection.js` | OpenSearch, Qdrant |

### LLM widget behavior (`testSemanticSearchConnection`)

1. Reads the mapped `provider` field value (`openai` or `ollama`).
2. Posts `provider` plus only the mapping keys whose names **include** the provider string (for example `openai_model`, `openai_api_key`, `openai_base_url`).
3. Updates the button to success/fail text; shows a modal alert when `errorMessage` is present.

### Vector-storage widget behavior (`testVectorStorageConnection`)

1. Posts **all** mapped fields (no provider filtering).
2. Same success/fail button and alert behavior as the LLM widget.

## JSON response contract

Every controller returns:

```json
{
  "success": true,
  "errorMessage": ""
}
```

On failure, `success` stays `false` and `errorMessage` may contain a localized or tag-filtered message.

## LLM test controllers

`TestEmbeddingConnection`, `TestGuardrailsConnection`, and `TestPersonalizedMessageConnection` extend `Controller\Adminhtml\SystemConfig\AbstractTestLLMConnection`.

Shared behavior:

- HTTP POST only (`HttpPostActionInterface`).
- Builds an AI client from request params via `AiClientPoolInterface::create($provider, $options)`.
- Treats success as `$client->isAvailable() === true`.
- Logs success at debug (`[ConfigTest] ...`) and failures at error.

Provider options assembled from POST params:

| Provider | Options |
|---|---|
| `openai` | `baseUrl`, `model`, `apiKey` |
| `ollama` | `baseUrl`, `model` |

Empty `baseUrl` is omitted before client creation.

### Field mappings (HTML element IDs)

**Embedding** (`Embedding\TestConnection`):

| POST key | Admin field HTML ID |
|---|---|
| `provider` | `magesetu_semantic_search_embedding_llm_provider` |
| `openai_model` | `magesetu_semantic_search_embedding_llm_openai_model` |
| `openai_api_key` | `magesetu_semantic_search_embedding_llm_openai_api_key` |
| `openai_base_url` | `magesetu_semantic_search_embedding_llm_openai_base_url` |
| `ollama_model` | `magesetu_semantic_search_embedding_llm_ollama_model` |
| `ollama_base_url` | `magesetu_semantic_search_embedding_llm_ollama_base_url` |

**Guardrails** (`Guardrails\TestConnection`):

| POST key | Admin field HTML ID |
|---|---|
| `provider` | `magesetu_semantic_search_guardrails_provider` |
| `openai_model` | `magesetu_semantic_search_guardrails_openai_model` |
| `openai_api_key` | `magesetu_semantic_search_guardrails_openai_api_key` |
| `openai_base_url` | `magesetu_semantic_search_guardrails_openai_base_url` |
| `ollama_model` | `magesetu_semantic_search_guardrails_ollama_model` |
| `ollama_base_url` | `magesetu_semantic_search_guardrails_ollama_base_url` |

**Personalized message** (`PersonalizedMessage\TestConnection`):

| POST key | Admin field HTML ID |
|---|---|
| `provider` | `magesetu_semantic_search_personalized_message_provider` |
| `openai_model` | `magesetu_semantic_search_personalized_message_openai_model` |
| `openai_api_key` | `magesetu_semantic_search_personalized_message_openai_api_key` |
| `openai_base_url` | `magesetu_semantic_search_personalized_message_openai_base_url` |
| `ollama_model` | `magesetu_semantic_search_personalized_message_ollama_model` |
| `ollama_base_url` | `magesetu_semantic_search_personalized_message_base_url` |

## OpenSearch test controller

`TestOpenSearchConnection`:

- Builds an OpenSearch Guzzle client from POST params: `server_url`, `timeout`, optional `username`/`password` when `is_auth_enabled` is truthy.
- Issues `GET /` and succeeds only when `version.distribution === 'opensearch'`.

Field mapping:

| POST key | Admin field HTML ID |
|---|---|
| `server_url` | `magesetu_semantic_search_vectordb_opensearch_server_url` |
| `is_auth_enabled` | `magesetu_semantic_search_vectordb_opensearch_enable_auth` |
| `username` | `magesetu_semantic_search_vectordb_opensearch_username` |
| `password` | `magesetu_semantic_search_vectordb_opensearch_password` |
| `timeout` | `magesetu_semantic_search_vectordb_opensearch_server_timeout` |

## Qdrant test controller

`TestQdrantConnection`:

- Calls `{api_url}/readyz` via `MageSetu\Common\Http\Client\Curl`.
- Sends `api-key` header when `api_token` is present.
- Treats any HTTP 2xx as success (readyz returns a plain-text body, not a JSON status envelope).
- Connect timeout 10s / request timeout 60s.

Field mapping:

| POST key | Admin field HTML ID |
|---|---|
| `api_url` | `magesetu_semantic_search_vectordb_qdrant_api_url` |
| `api_token` | `magesetu_semantic_search_vectordb_qdrant_api_token` |

## DI pool injection for LLM tests

In `etc/di.xml`, each LLM test controller receives a distinct `AiClientPool` virtual type so availability checks use the correct capability factories:

| Controller | Injected `aiClientPool` | Capability |
|---|---|---|
| `TestEmbeddingConnection` | `EmbeddingClientPool` | `EmbeddingClientInterface` |
| `TestPersonalizedMessageConnection` | `PersonalizedMessageClientPool` | `ChatClientInterface` |
| `TestGuardrailsConnection` | `GuardrailsClientPool` | `ChatClientInterface` |

# Integration / Flow

```text
Admin Stores > Configuration
  → system.xml button field + frontend_model
    → AbstractTestConnection renders PHTML + field_mapping
      → RequireJS widget collects live form values
        → POST magesetu_semanticsearch/systemConfig/<action>
          → Controller validates connectivity
            → JSON { success, errorMessage }
              → Button label / modal alert updated
```

Related configuration surfaces:

- Field declarations: `etc/adminhtml/system.xml`
- Admin route: `etc/adminhtml/routes.xml` (`magesetu_semanticsearch`)
- ACL: `MageSetu_SemanticSearch::config`
- Broader config defaults: [system-config-and-defaults.md](system-config-and-defaults.md)
- Admin routes: [routes.md](routes.md)
- Pool wiring: [../global/di-global-configuration.md](../global/di-global-configuration.md)

# Gotchas & Developer Notes

- **Unsaved values:** Tests read the current form DOM, so you can validate credentials before Save Config.
- **Not a full functional probe:** LLM tests call `isAvailable()`; they do not generate an embedding or run a guardrails/personalized-message prompt. OpenSearch only checks cluster identity; Qdrant only checks `/readyz`.
- **OpenSearch distribution check:** a reachable Elasticsearch (or other) node that does not report `distribution: opensearch` is reported as a failed connection even if HTTP succeeds.
- **Scope:** OpenSearch and Qdrant test buttons are default-scope only; website/store views do not show them.
- **Secrets in transit:** AJAX posts include API keys/passwords from the form; use HTTPS for admin and treat failed responses carefully in logs.
- **Vector DB `depends` on `provider`:** OpenSearch/Qdrant button `depends` nodes also reference `provider` with `negative="1"` inside the `vectordb` group (which uses `storage`, not `provider`). Visibility is primarily driven by the `storage` condition.
