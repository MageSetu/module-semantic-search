---
type: configuration
name: Frontend routes
description: Documents the frontend route configuration defined in etc/frontend/routes.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, frontend, routes]
module: MageSetu_SemanticSearch
---

# Overview

The frontend route configuration is defined in etc/frontend/routes.xml. It registers a custom frontend route so the module can expose public-facing URLs for its search-related functionality.

## Route definition

- Router ID: standard
- Route ID: magesetu_semantic_search
- Front name: ai-search
- Module: MageSetu_SemanticSearch

## Purpose

This route enables the module to expose frontend URLs such as:

```text
/ai-search/<controller>/<action>
```

## Configuration snippet

```xml
<router id="standard">
    <route id="magesetu_semantic_search" frontName="ai-search">
        <module name="MageSetu_SemanticSearch" />
    </route>
</router>
```

## Integration notes

- The route is scoped to the frontend area and is used for storefront controllers.
- The frontName ai-search becomes the URL prefix for the module’s public pages.
- Controllers under this route should be placed in the module’s frontend controller namespace.
