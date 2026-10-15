---
type: database-schema
name: ai_search_context product attribute data patch
description: Data patch that creates the store-scoped ai_search_context product EAV attribute—a free-text field for curated semantic context (including simplified price/offer cues and AI SEO copy)—and assigns it to every product attribute set.
tags: [magento2, magesetu, semantic-search, database-schema, eav, data-patch]
module: MageSetu_SemanticSearch
---

# Overview

`Setup/Patch/Data/AddAiSearchContextAttribute.php` is a Magento data patch that installs the catalog product attribute `ai_search_context`. Merchants use this textarea field to supply dedicated semantic/AI search context text. The attribute is created once if missing, then attached to the **MageSetu Ai Options** group on every product attribute set.

## Purpose

`ai_search_context` is a curated text surface for embedding and AI-assisted discovery—not a dump of Magento’s full catalog pricing model.

Magento’s product pricing is highly layered (tier prices, special/catalog rules, customer-group and website/store scopes, tax, and related indexers). Pulling that complexity into every semantic embedding would be expensive and noisy. Instead, merchants (or automation) can write concise, search-friendly price and offer cues into this field—for example “from $49,” “on sale this season,” or “includes free shipping”—without resolving the full price calculation pipeline at index time.

The same field doubles as an **AI SEO** channel: natural-language synonyms, use cases, audience intent, and other discoverability phrases that names and descriptions may not cover, so semantic search can match how shoppers actually ask for products.

## Patch Summary

| Item | Value |
| --- | --- |
| Class | `MageSetu\SemanticSearch\Setup\Patch\Data\AddAiSearchContextAttribute` |
| Path | `Setup/Patch/Data/AddAiSearchContextAttribute.php` |
| Interface | `Magento\Framework\Setup\Patch\DataPatchInterface` |
| Dependencies | None (`getDependencies()` returns `[]`) |
| Aliases | None (`getAliases()` returns `[]`) |

## Attribute Specifications

| Property | Value |
| --- | --- |
| Attribute code | `ai_search_context` |
| Admin label | `Ai Search Context` |
| Attribute group | `MageSetu Ai Options` (sort order `800` when created) |
| Backend type | `text` |
| Frontend input | `textarea` |
| Scope | Store view (`ScopedAttributeInterface::SCOPE_STORE`) |
| Required | No |
| User-defined | No (system attribute) |
| Sort order | `90` |
| Visible in admin | Yes |
| Visible on storefront | No |
| WYSIWYG / HTML on front | Disabled |
| Searchable / filterable / comparable | No |
| Advanced search / listing / sort | No |
| Unique | No |
| Default value | `null` |

## Apply Flow

1. Start the setup connection and create an `EavSetup` instance.
2. Look up `ai_search_context` on the product entity. If it does not exist, call `addAttribute()` with the options above (including the target group name).
3. Resolve the product entity type ID and load all attribute set IDs.
4. For each attribute set:
   - Ensure the **MageSetu Ai Options** group exists (`addAttributeGroup` when missing).
   - Call `addAttributeToSet()` so the attribute is present in that group.
5. End setup and return the patch instance.

Re-running the patch after the attribute already exists skips creation but still re-assigns the attribute to every attribute set under the group.

## Integration / Flow

- Module setup applies this patch during `setup:upgrade`, registering the EAV attribute in Magento’s catalog attribute tables.
- Default semantic embedding source attributes include `ai_search_context` via `etc/config.xml` (`magesetu_semantic_search/general/semantic_attributes` = `name,ai_search_context,short_description,description`). See [System configuration and defaults](../configurations/adminhtml/system-config-and-defaults.md).
- Store scope matches semantic content that can differ per store view when embeddings are built.
- Catalog product edit forms show the field under **MageSetu Ai Options** for all attribute sets present at patch time.

## Gotchas & Developer Notes

- The attribute is not user-defined (`user_defined` = false), so it behaves as a module-owned system attribute.
- New attribute sets created after the patch runs are not automatically updated by this patch; assign the attribute (or re-run equivalent assignment logic) for sets added later.
- Because the attribute is store-scoped, multi-store catalogs can hold different context text per store view; content hashing and embedding pipelines should load the correct store value.
- The patch is idempotent for attribute creation (`getAttributeId` guard) but always walks all attribute sets to ensure group membership.
- Docblock mentions “MageSetu Semantic Search group” while the constant and created group name are **MageSetu Ai Options**; treat the constant as the source of truth.
