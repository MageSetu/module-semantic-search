---
type: feature
name: Semantic Search Product entity and data access layer
description: Documents the interface, model, resource model, and collection used to represent and manage semantic search product rows for the magesetu_semantic_search_product table.
tags: [magento2, magesetu, semantic-search, model, resource-model, collection]
module: MageSetu_SemanticSearch
---

# Overview

The semantic search product entity is the Magento data layer used to represent a single record in the magesetu_semantic_search_product table. It provides a typed contract for the row fields, a Magento model for object access, a resource model for database operations, and a collection class for bulk query and update scenarios.

## Component Summary

- Interface: MageSetu\SemanticSearch\Api\Data\ProductInterface
- Model: MageSetu\SemanticSearch\Model\Product
- Resource model: MageSetu\SemanticSearch\Model\ResourceModel\Product
- Collection: MageSetu\SemanticSearch\Model\ResourceModel\Product\Collection

## Interface Contract

The interface defines the data contract for the semantic search product row and exposes the fields mapped to the database table.

### Field Constants

- id
- product_id
- store_id
- content_hash
- embedding_status
- message
- created_at
- updated_at

### Embedding Status Constants

- EMBEDDING_STATUS_PENDING = 0
- EMBEDDING_STATUS_SUCCESS = 1
- EMBEDDING_STATUS_FAILED = 2

### Public Methods

The interface exposes getters and setters for each field, including:

- getId/setId
- getProductId/setProductId
- getStoreId/setStoreId
- getContentHash/setContentHash
- getEmbeddingStatus/setEmbeddingStatus
- getMessage/setMessage
- getCreatedAt/setCreatedAt
- getUpdatedAt/setUpdatedAt

## Model Implementation

The model class extends Magento\Framework\Model\AbstractModel and implements the product interface plus IdentityInterface.

### Responsibilities

- Maps the model to the resource model through _construct()
- Exposes typed accessors for all table columns
- Provides cache identity support for Magento caching
- Uses the cache tag magesetu_semantic_search_product

### Key Characteristics

- The model is intentionally thin and acts as a data object rather than a business service.
- Getter methods cast values to the expected PHP types.
- The model event prefix is magesetu_semantic_search_product.

## Resource Model Implementation

The resource model extends Magento\Framework\Model\ResourceModel\Db\AbstractDb and binds the model to the magesetu_semantic_search_product table.

### Main Responsibilities

- Initialize the main table and primary key field
- Execute store-scoped lookups
- Support bulk insert/update operations
- Retrieve existing hashes for efficient comparison logic

### Key Methods

- getProductIdsByStoreId(int $storeId): array
  - Returns the product IDs associated with a store.
- deleteAllProductsFromStoreId(int $storeId): int
  - Deletes all rows for a specific store.
- persistBulkInsert(array $bulkInsertData, array $updateFields): int
  - Uses insertOnDuplicate to keep the unique (product_id, store_id) constraint efficient.
- getExistingHashesForBatch(array $productIds, int $storeId): array
  - Retrieves current hashes and embedding status values for a batch of products in one query.

### Design Notes

The resource model is optimized for batch embedding workflows. The bulk insert path is especially important because it avoids N+1 database access patterns when comparing content hashes.

## Collection Implementation

The collection class extends Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection and provides collection-level helper methods for mass updates and deletes.

### Key Methods

- updateColumnValue(string $field, mixed $value, int $chunkSize = 1000): int
  - Updates a single column on all loaded rows in chunks and wraps the operation in a transaction.
- deleteItems(int $chunkSize = 1000): int
  - Deletes all loaded rows in chunks and wraps the operation in a transaction.

### Purpose

This collection is useful when the module needs to perform bulk maintenance operations across the semantic search product records without loading each row individually in application code.

## Integration and Flow

This data layer is used by Magento’s model/resource/collection pattern to manage semantic search state for products.

Typical flow:

1. A product is loaded or created as a model instance.
2. The model is persisted through the resource model.
3. Bulk operations can use the collection for mass updates or deletes.
4. Store-scoped logic can retrieve product IDs or hashes efficiently for embedding comparison and reprocessing.

The collection is also wired into the module’s admin UI configuration, allowing Magento to expose the table through a UI grid data source.

## Gotchas and Developer Notes

- The table identity is effectively the combination of product_id and store_id, not just the primary key.
- The resource model depends on the unique constraint to support efficient upserts.
- Bulk operations should use reasonable chunk sizes to avoid large transactions and memory spikes.
- The embedding_status values are application-defined; any UI or service layer should interpret them consistently.
- The model itself does not contain business logic; it is primarily a data-access wrapper around the database row.
