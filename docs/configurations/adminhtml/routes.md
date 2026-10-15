---
type: configuration
name: Adminhtml routes
description: Documents the adminhtml route configuration defined in etc/adminhtml/routes.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, adminhtml, routes]
module: MageSetu_SemanticSearch
---

# Overview

The adminhtml route configuration is defined in etc/adminhtml/routes.xml. It registers a custom admin router and assigns a frontName used by Magento admin controllers in this module.

## Route definition

- Router ID: admin
- Route ID: magesetu_semanticsearch
- Front name: magesetu_semanticsearch
- Module: MageSetu_SemanticSearch

## Purpose

This route enables the module to expose admin-area URLs such as:

```text
/admin/magesetu_semanticsearch/<controller>/<action>
```

Examples used by system-configuration connection tests:

```text
/admin/magesetu_semanticsearch/systemConfig/testEmbeddingConnection
/admin/magesetu_semanticsearch/systemConfig/testPersonalizedMessageConnection
/admin/magesetu_semanticsearch/systemConfig/testGuardrailsConnection
/admin/magesetu_semanticsearch/systemConfig/testOpenSearchConnection
/admin/magesetu_semanticsearch/systemConfig/testQdrantConnection
```

See [system-config-test-connections.md](system-config-test-connections.md).
## Configuration snippet

```xml
<router id="admin">
    <route id="magesetu_semanticsearch" frontName="magesetu_semanticsearch">
        <module name="MageSetu_SemanticSearch"/>
    </route>
</router>
```

## Integration notes

- The route is scoped to the adminhtml area and is only used for backend controllers.
- The frontName is the URL segment that Magento appends after the admin base path.
- Controllers under this route should be placed in the module’s Adminhtml controller namespace.
