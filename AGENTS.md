# AGENTS.md

## Purpose

This repository is a Magento 2 module named MageSetu_SemanticSearch. It adds an AI-assisted semantic search layer alongside Magento’s native catalog search by indexing product content, generating embeddings, storing vectors, and serving semantic results from the storefront.

## First principle

Read the documentation before inspecting implementation.

The repository’s documentation in [README.md](README.md) and [CONTEXT.md](CONTEXT.md) is the primary source of truth. Documentation is part of the implementation here: keep it synchronized with code changes, and preserve the terminology already used in both code and docs.

## Where to start

Start with these files in order:

1. [README.md](README.md) — project purpose, install flow, and high-level features.
2. [CONTEXT.md](CONTEXT.md) — architecture overview and the main pipelines.
3. [docs/index.md](docs/index.md) — the documentation hub.

Do not begin with the implementation folders unless you already understand the module’s flow.

## Repository layout

- [etc](etc) — Magento configuration for DI, routing, events, indexers, queue topology, and admin system configuration.
- [Model](Model) — core business logic, config access, services, cache handling, and vector-storage integration.
- [Observer](Observer) and [Plugin](Plugin) — lifecycle hooks that react to catalog events and configuration changes.
- [Controller](Controller) and [view](view) — storefront and admin UI entry points, templates, and UI components.
- [Setup](Setup) — declarative schema and data patches.
- [docs](docs) — structured technical documentation and implementation notes.

## Documentation hierarchy

The docs are organized by concern rather than by file type:

- [docs/configurations](docs/configurations) — Magento configuration, DI wiring, ACL, routes, and indexer setup.
- [docs/features/semantic-search](docs/features/semantic-search) — feature-level flows such as indexing, queue processing, frontend search, and services.
- [docs/database-schema](docs/database-schema) — schema and EAV attribute documentation.
- [docs/utilities](docs/utilities) — exception and utility documentation.

When changing behavior, read the relevant feature doc before editing implementation.

## High-level architecture

The module is built around three main flows:

- Catalog lifecycle and indexing: product changes trigger observers/plugins and feed the semantic indexer.
- Queue-based embedding work: indexer output is handed off to asynchronous consumers that generate embeddings and write them to vector storage.
- Storefront search: a natural-language query is validated, converted to a vector, matched against vectors, and rendered through the frontend experience.

These flows are intentionally separated. Changes in one layer can affect the others, especially around config, indexing, queue publishing, and vector storage selection.

## Important concepts and terminology

Use the existing terminology consistently:

- semantic search / semantic-search
- vector storage / vector database
- embedding generation
- guardrails
- personalized message
- collection prefix
- semantic attributes

Avoid introducing new names for existing concepts unless the codebase already uses them.

## Expected development workflow

- Understand the relevant docs first.
- Read the implementation files that own the behavior you are changing.
- Prefer changes that fit existing patterns and Magento conventions.
- Keep documentation in sync with code changes.
- Avoid large refactors unless explicitly requested.

## Files to read before editing specific areas

Use these as starting points:

- For indexing and catalog lifecycle changes: [etc/indexer.xml](etc/indexer.xml), [etc/events.xml](etc/events.xml), [Observer](Observer), [Plugin](Plugin), and the indexer classes under [Model/Indexer](Model/Indexer).
- For queue and embedding behavior: [etc/queue_consumer.xml](etc/queue_consumer.xml), [etc/queue_publisher.xml](etc/queue_publisher.xml), [Model/Service](Model/Service), and the related queue publisher/consumer classes.
- For storefront search behavior: [Controller](Controller), [Block](Block), [view](view), and [Model/SemanticSearch.php](Model/SemanticSearch.php).
- For configuration changes: [etc/adminhtml/system.xml](etc/adminhtml/system.xml), [etc/config.xml](etc/config.xml), [etc/di.xml](etc/di.xml), and the config-related classes under [Model/Config](Model/Config).
- For schema or attribute changes: [Setup](Setup) and the relevant docs under [docs/database-schema](docs/database-schema).

## Design principles to respect

- Preserve the existing architecture unless the task explicitly calls for a refactor.
- Follow Magento module conventions for configuration, events, observers, plugins, and declarative schema.
- Keep changes compatible with the module’s existing extension points.
- Favor narrow, targeted fixes over broad rewrites.
- Integrate new features into existing patterns rather than introducing parallel implementations.

## Common mistakes to avoid

- Editing implementation before reading the relevant docs.
- Changing terminology in a way that diverges from the docs or existing code.
- Treating this as a standalone PHP app rather than a Magento 2 module.
- Making config changes without considering store-scope and website-scope implications.
- Changing queue/indexer behavior without understanding the asynchronous flow.
- Updating code without updating the accompanying documentation when behavior changes.

## Side-effect-prone areas

These areas are especially sensitive because they affect multiple layers:

- indexer and mview configuration
- queue publishing/consumption
- vector storage provider selection
- admin configuration and defaults
- product lifecycle observers/plugins
- any change that affects cache invalidation or status persistence

## Documentation expectations

When behavior changes, update the relevant document in [docs](docs) if the change affects architecture, configuration, workflows, or terminology. Keep docs concise and implementation-focused.

## Magento-specific conventions

- Prefer Magento’s native mechanisms for configuration, events, and dependency injection.
- Respect module declaration and sequence dependencies in [etc/module.xml](etc/module.xml).
- Keep schema changes declarative where possible.
- Be mindful of admin-store-website scope and the difference between runtime config and deployed config.

## Testing and verification

This repository does not document a dedicated test suite. When making changes, verify them through the most relevant Magento-side checks available in your environment and call out any limitations clearly.

If you are unsure about a repository detail, do not guess. State the gap and rely on the existing documentation instead.
