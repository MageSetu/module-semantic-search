# MageSetu Semantic Search for Magento 2

MageSetu Semantic Search is a Magento 2 module that adds AI-powered semantic product discovery alongside Magento’s native catalog search. It indexes product content, generates vector embeddings asynchronously, and retrieves relevant products from vector stores such as OpenSearch or Qdrant.

## Why this module

This module is designed for merchants and developers who want an intelligent search experience that goes beyond keyword matching. It helps surface products based on meaning, context, and similarity rather than exact text matches.

## Key features

- Semantic product indexing for catalog content
- Asynchronous embedding generation via Magento message queues
- Support for OpenSearch and Qdrant vector storage backends
- Admin configuration for embeddings, guardrails, personalization, and vector settings
- Frontend AI-style search experience with a dedicated storefront flow
- CLI maintenance commands for orphan cleanup and environment prefix inspection
- Detailed module documentation for configuration, services, and architecture

## Requirements

- PHP 8.4 or newer
- Magento 2.4.8 or newer
- Composer dependencies:
  - magesetu/module-common
  - magesetu/module-ai-base

## Installation

### Composer-based installation

```bash
composer require magesetu/module-semantic-search
bin/magento module:enable MageSetu_SemanticSearch
bin/magento setup:upgrade
bin/magento cache:flush
```

### Local module development

If you are working from this repository directly, place the module under your Magento installation in the expected module path and run the same setup commands.

## Configuration

After installation, configure the module from the Magento admin panel:

- Enable the module
- Select the embedding provider and model
- Choose the vector storage backend
- Configure guardrails and personalized-message options
- Set a collection prefix for multi-environment isolation if needed

The detailed configuration guide is available in [docs/configurations/adminhtml/system-config-and-defaults.md](docs/configurations/adminhtml/system-config-and-defaults.md).

## Usage

Once enabled, the module will:

1. Collect semantic product content during indexing
2. Prepare payloads for embedding generation
3. Send work to the queue for asynchronous processing
4. Store embeddings in the selected vector database
5. Serve semantic results through the AI search experience

You can also use the CLI commands documented in [docs/features/semantic-search/console-commands.md](docs/features/semantic-search/console-commands.md) to inspect or clean up vector data.

## Architecture at a glance

The module is organized around a few core stages:

- Indexer flow: prepares product data for semantic processing
- Payload processing: decides whether content needs to be re-queued
- Embedding generation: converts product context into vectors
- Vector storage: stores and retrieves similar product embeddings
- Frontend search flow: handles the storefront request and response experience

## Documentation

The repository includes a structured documentation set under [docs](docs) covering configuration, services, features, and database schema.

Recommended starting points:

- [docs/index.md](docs/index.md) — documentation hub
- [docs/features/semantic-search/frontend-search-experience.md](docs/features/semantic-search/frontend-search-experience.md) — storefront flow
- [docs/features/semantic-search/indexer-flow.md](docs/features/semantic-search/indexer-flow.md) — indexing pipeline
- [docs/features/semantic-search/console-commands.md](docs/features/semantic-search/console-commands.md) — maintenance and diagnostics
- [docs/configurations/adminhtml/system-config-and-defaults.md](docs/configurations/adminhtml/system-config-and-defaults.md) — admin configuration reference

## Disclaimer

> [!WARNING]
> **Use this module at your own risk.** 
> This module is provided "as is" without warranty of any kind, either express or implied. The developers are not responsible for any data loss, API rate limit overages, financial charges from external LLM providers (e.g. OpenAI), server downtime, or other issues resulting from the use or installation of this software.

## License

This project is licensed under the Apache License 2.0. See [LICENSE](LICENSE) for details.
