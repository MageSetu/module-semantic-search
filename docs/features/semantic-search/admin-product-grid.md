---
type: feature
name: Semantic Search product admin grid
description: Documents the backend grid that displays semantic-search tracking rows for products, including content hash, embedding status, and bulk maintenance actions.
tags: [magento2, magesetu, semantic-search, admin, ui-grid]
module: MageSetu_SemanticSearch
---

# Semantic Search Product Admin Grid

The admin grid provides an internal management view for the semantic-search product tracking records. It lets a Magento administrator review per-product, per-store embedding metadata and perform bulk maintenance actions without modifying the underlying catalog product directly.

## Overview

The grid is intended for operational visibility and correction. It surfaces the main state that the module uses to decide whether a product’s content should be re-embedded or whether a previous embedding attempt failed.

## What the Grid Shows

The listing displays these core fields:

- ID — internal row identifier.
- Product ID — Magento catalog product identifier.
- Store ID — store view context for the row.
- Content Hash — deterministic hash of the content used for embedding.
- Embedding Status — current lifecycle state of the record.
- Message — optional diagnostic or error text.
- Created At / Updated At — row lifecycle timestamps.

## Status Values

The grid uses the following status values for the `embedding_status` field:

- `0` — Pending
- `1` — Success / Finished
- `2` — Failed

These values are mapped in the UI component configuration and should remain consistent with the database schema and controller logic.

## Mass Actions

The toolbar provides two bulk actions:

1. Delete
   - Removes the selected rows from the semantic-search tracking table.
   - Uses the mass delete controller and the resource-model collection delete logic.

2. Update Embedding Status
   - Allows selected rows to be updated to either Pending or Finished (Success).
   - The status change is handled by the mass status controller and applied to the selected collection.

## Integration and Flow

The admin grid is composed of the following pieces:

- The grid controller renders the admin page.
- The UI component XML configures the listing, columns, filters, and mass actions.
- The data source is wired to the custom collection backed by the semantic-search product resource model.
- The mass action controllers apply the requested changes to the selected rows.

This flow ties the admin user interface to the same persistence layer described in the [semantic search product table](../../database-schema/magesetu-semantic-search-product-table.md).

## Related Implementation Points

- The page is routed through the admin products grid controller.
- The grid UI definition is declared in the admin UI component XML file.
- The mass actions are handled by dedicated admin controllers for delete and status updates.
- The underlying data provider uses the custom collection for the semantic-search product table.

## Gotchas and Developer Notes

- The grid requires the proper ACL resource, so only users with the corresponding permission can access it.
- Bulk actions should be used carefully because they update or remove tracking rows directly rather than re-running the full indexing pipeline.
- The status values are application-defined and should not be changed casually because they affect both UI display and downstream business logic.
- If the vector store already contains embeddings for a product, deleting the tracking row does not automatically remove the vector data unless the broader cleanup process is also invoked.

## Related Documents

- [Semantic Search documentation index](index.md)
- [Semantic Search console commands](console-commands.md)
- [Semantic Search product table schema](../../database-schema/magesetu-semantic-search-product-table.md)
