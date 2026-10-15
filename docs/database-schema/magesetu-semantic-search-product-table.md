---
type: database-schema
name: magesetu_semantic_search_product table
description: Stores per-product, per-store embedding metadata for the semantic search module, including content hashes, processing status, and error messages.
tags: [magento2, magesetu, semantic-search, database-schema]
module: MageSetu_SemanticSearch
---

# Overview

The magesetu_semantic_search_product table is a Magento declarative schema table used by the semantic search module to track embedding state for catalog products on a per-store basis. It lets the module decide whether product content has changed, whether embeddings are pending, and whether a previous embedding attempt failed.

## Table Summary

- Table name: magesetu_semantic_search_product
- Storage engine: InnoDB
- Resource: default
- Purpose: persist embedding-related metadata for each product/store pair

## Columns

| Column | Type | Null | Default | Description |
| --- | --- | --- | --- | --- |
| id | int unsigned | No | Auto-increment | Primary key |
| product_id | int unsigned | No | None | References the catalog product entity |
| store_id | smallint unsigned | No | None | References the store view |
| content_hash | varchar(64) | No | None | SHA-256 hash of the content used for embedding |
| embedding_status | smallint unsigned | No | 0 | Status code: 0 = pending, 1 = success, 2 = failed |
| message | text | Yes | None | Optional error or diagnostic message |
| created_at | timestamp | No | CURRENT_TIMESTAMP | Row creation timestamp |
| updated_at | timestamp | No | CURRENT_TIMESTAMP | Row update timestamp |

## Constraints and Indexes

- Primary key on id
- Unique constraint on (product_id, store_id) to ensure one record per product per store
- Foreign key to catalog_product_entity.entity_id with cascade delete
- Foreign key to store.store_id with cascade delete
- B-tree index on store_id for store-scoped lookups

## Integration and Data Flow

This table is used as the main persistence layer for the semantic search product model. The resource model reads and writes rows through Magento’s declarative schema layer and uses the unique constraint to support efficient upserts for bulk processing.

Typical usage pattern:

1. The module computes or updates a content hash for a product in a given store.
2. The hash and embedding status are stored in this table.
3. Later runs compare the stored hash with the current content to decide whether re-embedding is needed.
4. Failed embedding attempts can be inspected through the message column.

## Gotchas and Developer Notes

- The combination of product_id and store_id is the effective business identity of a row, so changing either value requires careful handling.
- Because the table uses foreign keys with cascade delete, removing a product or store will delete associated rows automatically.
- embedding_status is application-defined; keep the numeric mapping consistent across all code paths and UI logic.
- The content_hash should be generated deterministically from the content being embedded to avoid unnecessary reprocessing.
