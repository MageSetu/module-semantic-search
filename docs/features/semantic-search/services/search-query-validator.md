---
type: feature
name: Search query validator service
description: Documents the service that validates and sanitizes user input before it is sent to the semantic-search pipeline.
tags: [magento2, magesetu, semantic-search, service, guardrails]
module: MageSetu_SemanticSearch
---

# Search Query Validator Service

The search query validator service checks whether a user query is safe and acceptable for the semantic-search experience before the search request proceeds.

## Responsibilities

- Sanitize the incoming query string.
- Optionally invoke guardrails through an AI provider.
- Cache validation results per query and store.
- Throw a localized exception when the query is rejected.

## Main flow

1. The service receives a raw query and store ID.
2. It escapes the input and checks whether guardrails are enabled.
3. It attempts a cached validation result first.
4. If needed, it calls the configured guardrail provider and evaluates the response.
5. It returns the original query or throws an exception if blocked.

## Key implementation references

- <magento-root>/app/code/MageSetu/SemanticSearch/Model/Service/SearchQueryValidator.php

## Notes

This service sits near the entry point of the semantic-search user flow and helps enforce policy and safety before search execution.
