---
type: configuration
name: "Vector Storage Collection Prefix Configuration"
description: "Details how to configure the environment-specific collection prefix for vector storage naming."
tags: [magento2, magesetu, configuration, environment, deploy, opensearch]
module: "MageSetu_SemanticSearch"
---

# Vector Storage Collection Prefix Configuration

To prevent environments (Production, Staging, Dev) from sharing or overwriting the same vector database collections, `MageSetu_SemanticSearch` supports setting an environment-specific collection prefix.

This value is stored in `app/etc/env.php` (deployment configuration) rather than the Magento database, which ensures that database dumps/restores between environments do not accidentally override this setting.

## Setting the Collection Prefix

You can configure the prefix using the standard Magento configuration commands.

### CLI Option
The module registers the `--semantic-search-collection-prefix` parameter for Magento setup commands.

```bash
bin/magento setup:config:set --semantic-search-collection-prefix="staging"
```

### Constraints
- The value must only contain alphanumeric characters and underscores (`a-z`, `A-Z`, `0-9`, `_`).
- Empty or whitespace-only values are not allowed.

## How it works

When naming a collection/index for a specific store view (e.g. store ID `1`):
- If the prefix is set to `staging`, the collection name will be: `staging_magesetu_semantic_search_products_1`.
- If no prefix is set, the collection name defaults to: `magesetu_semantic_search_products_1`.

This namespace isolation allows multiple staging or development environments to safely share the same vector storage cluster without data collision.
