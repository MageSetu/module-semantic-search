---
type: feature
name: Semantic Search console commands
description: Documents the CLI commands that expose semantic-search maintenance and diagnostics capabilities, including orphan cleanup and vector collection prefix inspection.
tags: [magento2, magesetu, semantic-search, cli, console]
module: MageSetu_SemanticSearch
---

# Semantic Search Console Commands

The module exposes a small set of console commands under the `magesetu:semantic-search` namespace to support operational maintenance of the semantic-search embedding layer. These commands are registered through the Magento console command list and are intended for administrators and developers who need to inspect or clean up embedding-related state.

## Overview

The current command set covers two practical use cases:

1. Removing orphaned vector documents that no longer match valid Magento products.
2. Showing the current vector storage collection prefix used by the environment resolver.

## Commands

### 1. Remove orphaned embeddings

**Command name:**

```bash
php bin/magento magesetu:semantic-search:remove-orphaned
```

**Options**

| Option | Shortcut | Type | Description |
|---|---|---|---|
| `--store-id` | None | Optional value | Limit processing to a specific Magento Store ID. Defaults to processing all stores. |
| `--dry-run` | None | Flag | Executes the command in simulation mode. Identifies and prints the number of orphans without deleting them. |
| `--batch-size` | None | Optional value | Batch size for document scroll and bulk deletion operations. (Default: `1000`). |

**Examples**

- Dry-run simulation for all stores:
  ```bash
  bin/magento magesetu:semantic-search:remove-orphaned --dry-run
  ```
- Perform cleanup for store ID 1 only with custom batch size:
  ```bash
  bin/magento magesetu:semantic-search:remove-orphaned --store-id=1 --batch-size=500
  ```

**Purpose:**

- Remove vector database entries that are no longer tied to valid catalog products.
- Support store-scoped or all-store cleanup.
- Allow a dry run before any destructive action.

**Supported options:**

- `--store-id` — limit processing to a specific store view.
- `--dry-run` — report orphaned records without deleting them.
- `--batch-size` — configure the batch size used during cleanup.

**Behavior:**

- The command sets the admin area state before execution.
- It iterates through the requested stores and calls the orphaned embedding cleaner service.
- On success, it returns a CLI success status; otherwise it reports an error.

### 2. Show collection prefix

**Command name:**

```bash
php bin/magento magesetu:semantic-search:show-collection-prefix
```

**Response Examples**

- When prefix is configured:
  ```text
  Semantic search vector database environment prefix: staging
  ```
- When prefix is NOT configured:
  ```text
  No environment prefix is set for semantic search vector database.
  Example to set: bin/magento setup:config:set --semantic-search-collection-prefix="staging"
  ```

**Purpose:**

- Display the configured vector storage collection prefix sourced from the environment resolver.
- Help developers verify which collection namespace is active for the current environment.

**Behavior:**

- If no prefix is configured, the command reports that state and suggests the setup command to define one.
- If a prefix exists, it prints the current value to the console.

## Registration and Integration

These commands are registered in the Magento command list through the DI configuration described in [di-global-configuration.md](../../configurations/global/di-global-configuration.md). The wiring ensures that Magento discovers the commands automatically at runtime.

The command implementations rely on the following supporting services:

- The orphaned embedding cleaner service for cleanup logic.
- The environment resolver interface for prefix lookup.
- Magento’s store manager and application state services for runtime context.

## Related Data Model

The cleanup and inspection commands operate on the same semantic-search record model that is persisted in the [semantic search product table](../../database-schema/magesetu-semantic-search-product-table.md). In practice, the commands help keep the vector store and the metadata table aligned.

## Gotchas and Developer Notes

- The cleanup command runs in the admin area context, so it is suitable for backend maintenance tasks.
- Batch size should be tuned carefully when working with large catalogs to avoid excessive memory pressure or long-running CLI operations.
- Dry runs are especially useful in production environments before deleting vectors.
- The collection prefix value is environment-specific; changing it can affect how vector indexes are named and discovered.
