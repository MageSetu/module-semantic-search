---
type: utility
name: "Vector storage exceptions"
description: "Documents the module-specific exceptions used to signal vector-storage runtime failures and missing/unregistered storage providers."
tags: [magento2, magesetu, semantic-search, exception, vector-storage]
module: "MageSetu_SemanticSearch"
---

# Vector storage exceptions

`MageSetu_SemanticSearch` defines two dedicated exception classes under `Exception/` so callers can distinguish **storage operation failures** from **provider resolution failures** without catching broad PHP exceptions.

## Overview / Purpose

| Class | Extends | Purpose |
| --- | --- | --- |
| `VectorStorageException` | `\RuntimeException` | Thrown when a vector-storage operation fails (create/delete/search, client init, bulk writes, cleanup, and similar). |
| `VectorStorageNotFoundException` | `\InvalidArgumentException` | Thrown when the requested vector-storage provider code is missing, unregistered, or cannot be resolved from the pool. |

Both classes are empty marker types: they inherit message, code, and previous-exception behavior from their PHP parents and add no custom methods or properties.

## Technical Specifications

### `MageSetu\SemanticSearch\Exception\VectorStorageException`

```php
namespace MageSetu\SemanticSearch\Exception;

/**
 * Thrown when any vector-storage operation fails.
 */
class VectorStorageException extends \RuntimeException
{
}
```

- **Parent:** `\RuntimeException` (unchecked-style runtime failure).
- **Declared on:** `Api\VectorStorageInterface` operation contracts (`@throws VectorStorageException`).
- **Also declared on:** `Api\CleanVectorStorageInterface::execute()` (along with `VectorStorageNotFoundException`).

### `MageSetu\SemanticSearch\Exception\VectorStorageNotFoundException`

```php
namespace MageSetu\SemanticSearch\Exception;

class VectorStorageNotFoundException extends \InvalidArgumentException
{
}
```

- **Parent:** `\InvalidArgumentException` (invalid / unresolved provider argument).
- **Declared on:** `Api\VectorStoragePoolInterface::getByCode()` and `Api\VectorStorageProviderInterface` resolution paths.
- **Also declared on:** `Api\CleanVectorStorageInterface::execute()`.

## Integration / Flow

```text
Caller (indexer, queue consumer, CLI, admin test, search service)
        │
        ▼
StorageProvider / VectorStoragePool
        │  missing / unregistered code
        └─► VectorStorageNotFoundException
        │
        ▼
VectorStorageInterface implementation (OpenSearch / Qdrant)
        │  backend / client / bulk / search failure
        └─► VectorStorageException
```

### Where `VectorStorageException` is thrown

Typical throw sites wrap backend errors with a domain message (and often preserve the previous exception):

- `Model\Vector\Storage\OpenSearch` — index create/delete/exists, bulk save/delete, delete-by-query, and kNN search failures.
- `Model\Vector\Storage\Qdrant` — collection lifecycle, upsert/delete, and search failures.
- `Model\Search\OpenSearchClientProvider` — OpenSearch client initialization failure.
- `Model\Vector\CleanVectorStorage` — cleanup failure.
- `Model\Queue\RemoveEmbeddingConsumer` — missing vector collection during removal.

Service contracts advertise these throws so consumers (queue handlers, CLI, admin AJAX controllers) can catch storage failures specifically and mark work as failed, log context, or return a connection-test error.

### Where `VectorStorageNotFoundException` is thrown

- `Model\Vector\VectorStoragePool::getByCode()` — provider code is not registered in the pool (message lists available codes).
- `Model\Vector\StorageProvider` — wraps pool/resolution failures and rethrows as `VectorStorageNotFoundException` with message `Vector storage not found.` after logging.

## Gotchas & Developer Notes

- Prefer catching these module exceptions over `\Exception` when recovering from vector-storage errors; that keeps Magento/framework and configuration mismatches (for example `ConfigurationMismatchException` from an invalid pool entry) separate.
- `VectorStorageException` is a **runtime** failure of an already-selected backend. `VectorStorageNotFoundException` is a **configuration/selection** failure before a backend can be used. Retrying the same operation rarely helps the latter; fix provider config or DI pool registration instead.
- Implementations often log with contextual keys (provider, collection, store) before throwing. When rethrowing, preserve the previous exception so stack traces remain useful.
- Empty marker classes are intentional: do not add HTTP status mapping or Magento `LocalizedException` behavior here unless product requirements change; keep presentation/localization at the caller (admin controllers, CLI, API layer).

## Implementation references

- `Exception/VectorStorageException.php`
- `Exception/VectorStorageNotFoundException.php`
- `Api/VectorStorageInterface.php`
- `Api/VectorStoragePoolInterface.php`
- `Api/VectorStorageProviderInterface.php`
- `Api/CleanVectorStorageInterface.php`
- `Model/Vector/VectorStoragePool.php`
- `Model/Vector/StorageProvider.php`
- `Model/Vector/Storage/OpenSearch.php`
- `Model/Vector/Storage/Qdrant.php`
