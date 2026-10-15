---
type: feature
name: Payload processor service
description: Documents the service that validates whether pending semantic-search payloads need to be queued or persisted based on content-hash changes.
tags: [magento2, magesetu, semantic-search, service, payload]
module: MageSetu_SemanticSearch
---

# Payload Processor Service

The payload processor service evaluates product payloads before they are written to the semantic-search database and queue layer. Its role is to avoid redundant work by comparing the current semantic content against prior stored hashes.

## Responsibilities

- Build a deterministic content hash from provider, model, storage backend, and semantic attributes.
- Compare the hash with the current database state for each product.
- Decide whether a semantic-search row should be inserted or updated.
- Decide whether a product should be queued for embedding generation.

## Main flow

1. The service receives a batch of raw product payloads and a store ID.
2. It loads the existing semantic-search rows for the relevant products.
3. It computes a hash for each payload.
4. It marks a row as pending when the content changed or when earlier processing did not complete successfully.
5. It returns the DB insert rows and the queue payloads separately.

## Key implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Service/PayloadProcessor.php

## Notes

This is the gatekeeper between the indexer pipeline and the asynchronous embedding pipeline. It is responsible for preventing duplicate or stale queue work.
