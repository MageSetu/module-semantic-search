<?php
/**
 * Copyright (c) 2026 MageSetu. All rights reserved.
 *
 * @package    MageSetu_SemanticSearch
 * @author     MageSetu
 * @copyright  Copyright (c) 2026 MageSetu
 * @license    https://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link       https://github.com/MageSetu/module-semantic-search
 */

declare(strict_types=1);

namespace MageSetu\SemanticSearch\Api;

/**
 * Magento store configurations for Semantic Search Module
 *
 * @api
 * @since 1.0.0
 */
interface ConfigInterface
{
    /**
     * Is module enabled
     *
     * @param  null|int|string $scopeCode = null
     * @return bool
     */
    public function isModuleEnabled(null|int|string $scopeCode = null): bool;

    /**
     * Get product attributes from which embedding will be generated
     *
     * @param  null|int|string $scopeCode = null
     * @return array
     */
    public function getSemanticAttributes(null|int|string $scopeCode = null): array;

    /**
     * Get batch size to generate index in batches
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getIndexBatchSize(null|int|string $scopeCode = null): int;

    /**
     * Get batch size to generate embeddings in batches
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getEmbeddingBatchSize(null|int|string $scopeCode = null): int;

    /**
     * Get embedding LLM provider
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getEmbeddingProvider(null|int|string $scopeCode = null): string;

    /**
     * Get embedding cache lifetime
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getEmbeddingCacheLifetime(null|int|string $scopeCode = null): int;

    /**
     * Is guardrails enabled
     *
     * @param  null|int|string $scopeCode = null
     * @return bool
     */
    public function isGuardrailsEnabled(null|int|string $scopeCode = null): bool;

    /**
     * Get guardrails LLM provider
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getGuardrailsProvider(null|int|string $scopeCode = null): string;

    /**
     * Get guardrails system prompt
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getGuardSystemPrompt(null|int|string $scopeCode = null): string;

    /**
     * Get guardrails reasoning effort
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getGuardReasoningEffort(null|int|string $scopeCode = null): string;

    /**
     * Get guardrails error message
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getGuardErrorMessage(null|int|string $scopeCode = null): string;

    /**
     * Get guardrails max tokens
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getGuardMaxTokens(null|int|string $scopeCode = null): int;

    /**
     * Get guardrails cache lifetime
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getGuardCacheLifetime(null|int|string $scopeCode = null): int;

    /**
     * Get vector database storage
     *
     * @return string
     */
    public function getVectorDbStorage(): string;

    /**
     * Get vector database limit
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getVectorDbSearchLimit(null|int|string $scopeCode = null): int;

    /**
     * Get vector database score threshold
     *
     * @param  null|int|string $scopeCode = null
     * @return float
     */
    public function getVectorDbScoreThreshold(null|int|string $scopeCode = null): float;

    /**
     * Get open search URL including protocol and port
     *
     * @return string
     */
    public function getOpenSearchUrl(): string;

    /**
     * Get open search enable auth
     *
     * @return bool
     */
    public function isOpenSearchAuthEnabled(): bool;

    /**
     * Get open search username
     *
     * @return string
     */
    public function getOpenSearchUsername(): string;

    /**
     * Get open search password
     *
     * @return string
     */
    public function getOpenSearchPassword(): string;

    /**
     * Get open search timeout
     *
     * @return int
     */
    public function getOpenSearchTimeout(): int;

    /**
     * Get Qdrant api url
     *
     * @return string
     */
    public function getQdrantApiUrl(): string;

    /**
     * Get Qdrant api token
     *
     * @return string
     */
    public function getQdrantApiToken(): string;

    /**
     * Is personalized message enabled
     *
     * @param  null|int|string $scopeCode = null
     * @return bool
     */
    public function isPersonalizedMessageEnabled(null|int|string $scopeCode = null): bool;

    /**
     * Get personalized message LLM provider
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getPersonalizedMessageProvider(null|int|string $scopeCode = null): string;

    /**
     * Get LLM system context for personalized message
     *
     * @param  null|int|string $scopeCode = null
     * @return string
     */
    public function getPersonalizedMessageSystemContext(null|int|string $scopeCode = null): string;

    /**
     * Get LLM temperature for personalized message
     *
     * @param  null|int|string $scopeCode = null
     * @return float
     */
    public function getPersonalizedMessageTemperature(null|int|string $scopeCode = null): float;

    /**
     * Get LLM max tokens for personalized message
     *
     * @param  null|int|string $scopeCode = null
     * @return int
     */
    public function getPersonalizedMessageMaxTokens(null|int|string $scopeCode = null): int;

    /**
     * Get AI model options like model, base url and api key
     *
     * @param string $group
     * @param string $provider
     * @param null|integer|string|null $scopeCode
     * @return array
     */
    public function getAiModelOptions(
        string $group,
        string $provider,
        null|int|string $scopeCode = null
    ): array;
}
