---
type: feature
name: Environment resolver
description: Documents the environment resolver used by semantic-search vector storage implementations to read collection-prefix settings from Magento deployment configuration.
tags: [magento2, magesetu, semantic-search, environment, deployment-config]
module: MageSetu_SemanticSearch
---

# Environment resolver

The environment resolver is a small configuration bridge that reads the semantic-search collection prefix from Magento’s deployment configuration (`app/etc/env.php`) through `DeploymentConfig`.

## Purpose

The resolver is used by vector storage implementations to build stable collection names for the configured vector backend. It allows the module to keep collection naming environment-specific without hard-coding the prefix into the storage classes.

## Main responsibility

- Read the collection prefix from the deployment config path configured by `Setup/ConfigOptionsList.php`.
- Return an empty string when the value is not present, so callers can decide how to handle an unset prefix.
- Provide a consistent interface for vector storage classes and CLI commands that need the prefix.

## Implementation reference

- `MageSetu\SemanticSearch\Model\EnvironmentResolver`
- `MageSetu\SemanticSearch\Api\EnvironmentResolverInterface`
- `MageSetu\SemanticSearch\Setup\ConfigOptionsList`

## DI configuration reference

The interface is registered in `etc/di.xml` via a preference for `Api/EnvironmentResolverInterface`, which points the runtime implementation to `Model/EnvironmentResolver`.

## Runtime usage

The resolver is injected into the vector storage base classes and into the storage implementations that need the collection prefix for index and collection operations. It is also consumed by the console command that displays the current collection prefix in the CLI.

## Notes

The prefix is resolved from deployment configuration rather than from the admin config tree. This makes it suitable for environment-specific deployment values that should remain outside store-scoped admin settings.
