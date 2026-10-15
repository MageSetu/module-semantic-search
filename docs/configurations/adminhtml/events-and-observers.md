---
type: configuration
name: Adminhtml events and observers
description: Documents the adminhtml Magento events and observers registered in etc/adminhtml/events.xml for the semantic search module.
tags: [magento2, magesetu, semantic-search, adminhtml, events, observers]
module: MageSetu_SemanticSearch
---

# Overview

The adminhtml event wiring is defined in etc/adminhtml/events.xml. These observers are used when configuration changes are made through the Magento admin panel or when a store is removed.

## Registered events

### admin_system_config_changed_section_magesetu_semantic_search

- Observers:
  - MageSetu\SemanticSearch\Observer\HandleEmbeddingConfigChange
  - MageSetu\SemanticSearch\Observer\HandleGuardrailsConfigChange
  - MageSetu\SemanticSearch\Observer\HandleVectorStorageConfigChange
- Purpose: reacts when the semantic-search configuration section is changed in the admin area.
- Event payload: includes the changed config paths and the current store context.

### store_delete_commit_after

- Observer: MageSetu\SemanticSearch\Observer\DeleteVectorCollectionForStore
- Purpose: removes vector-collection data associated with a deleted store.
- Event payload: includes the store object that is being removed.

## Observer responsibilities

- HandleEmbeddingConfigChange: responds to embedding-related admin config updates.
- HandleGuardrailsConfigChange: responds to guardrails-related admin config updates.
- HandleVectorStorageConfigChange: responds to vector-storage-related admin config updates.
- DeleteVectorCollectionForStore: performs cleanup when a store is deleted to avoid orphaned data.

## Example configuration

```xml
<event name="admin_system_config_changed_section_magesetu_semantic_search">
    <observer name="magesetu_semanticsearch_handle_embedding_config_change"
        instance="MageSetu\SemanticSearch\Observer\HandleEmbeddingConfigChange" />
</event>
```

## Integration notes

- These listeners are specific to the adminhtml area and are relevant only when admin configuration is changed.
- They rely on the event data provided by Magento for config changes, especially changed_paths and the affected store.
- Cleanup and reconfiguration actions should be safe to run more than once because admin changes can trigger multiple updates.
