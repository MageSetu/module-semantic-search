---
type: configuration
name: ACL configuration
description: Documents the access-control resources defined in etc/acl.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, acl, permissions]
module: MageSetu_SemanticSearch
---

# Overview

The module registers its admin permissions in etc/acl.xml. These ACL resources control which backend roles can view, manage, and configure semantic search functionality.

## ACL resource hierarchy

The ACL tree begins under Magento backend administration and defines the following resources:

- Magento_Backend::admin
- MageSetu_Common::manage
- MageSetu_SemanticSearch::manage
- MageSetu_SemanticSearch::manage_products
- MageSetu_SemanticSearch::config

## Resource definitions

### MageSetu_SemanticSearch::manage

- Title: Semantic Search
- Sort order: 200
- Purpose: top-level permission for the module’s administrative capabilities.

### MageSetu_SemanticSearch::manage_products

- Title: Manage Products
- Sort order: 100
- Purpose: allows management of semantic-search product-related actions.

### MageSetu_SemanticSearch::config

- Title: Configurations
- Sort order: 700
- Purpose: grants access to the module’s system configuration section in the admin area.

## Integration notes

- The module uses the admin resource MageSetu_SemanticSearch::config for the configuration section defined in etc/adminhtml/system.xml.
- Permission inheritance is defined through the Magento backend ACL structure, so users with broader access to MageSetu_Common::manage can inherit the module-level permissions.
- These resources are important for securing administrative operations and preventing unauthorized configuration changes.
