---
type: configuration
name: DI configuration overview
description: Documents the main dependency injection configuration defined in etc/di.xml for the Magento semantic search module.
tags: [magento2, magesetu, semantic-search, di, object-manager]
module: MageSetu_SemanticSearch
---

# Overview

The module’s dependency injection configuration is defined in the global area file etc/di.xml. The module does not have separate area-specific DI files in this repository, so this overview documents what belongs to the global scope and clearly notes which pieces are relevant to adminhtml and frontend usage.

## Area-Based Split

- Global: preferences, logger setup, command registration, plugin definitions, AI client pools, and vector storage wiring
- Adminhtml: UI grid data source configuration and admin controller injection points
- Frontend: runtime service wiring for search, message generation, guardrails, and vector storage consumption

## Related Documents

- Global DI configuration: [global/di-global-configuration.md](global/di-global-configuration.md)
- Global events and observers: [global/events-and-observers.md](global/events-and-observers.md)
- Global ACL configuration: [acl.md](acl.md)
- Adminhtml routes: [adminhtml/routes.md](adminhtml/routes.md)
- Adminhtml events and observers: [adminhtml/events-and-observers.md](adminhtml/events-and-observers.md)
- Adminhtml system configuration and defaults: [adminhtml/system-config-and-defaults.md](adminhtml/system-config-and-defaults.md)
- Adminhtml system configuration test connection buttons: [adminhtml/system-config-test-connections.md](adminhtml/system-config-test-connections.md)
- Frontend routes: [frontend/routes.md](frontend/routes.md)

## Notes

This document is intentionally scoped to the contents of the existing DI configuration and explains how the same file is interpreted across Magento areas.
