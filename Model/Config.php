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

namespace MageSetu\SemanticSearch\Model;

use MageSetu\SemanticSearch\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Magento store configurations for Semantic Search Module
 *
 * @api
 * @since 1.0.0
 */
class Config implements ConfigInterface
{
    // Store configuration section path
    private const XML_PATH_SECTION = 'magesetu_semantic_search';

    // General Settings
    private const XML_PATH_IS_ENABLED = 'magesetu_semantic_search/general/enabled';
    private const XML_PATH_INDEX_BATCH_SIZE = 'magesetu_semantic_search/general/index_batch_size';
    private const XML_PATH_EMBEDDING_BATCH_SIZE = 'magesetu_semantic_search/general/embedding_batch_size';
    private const XML_PATH_SEMANTIC_ATTRIBUTES = 'magesetu_semantic_search/general/semantic_attributes';

    // Embedding LLM Settings
    private const XML_PATH_EMBEDDING_PROVIDER = 'magesetu_semantic_search/embedding_llm/provider';
    private const XML_PATH_EMBEDDING_CACHE_LIFETIME = 'magesetu_semantic_search/embedding_llm/cache_lifetime';

    // Guardrails LLM Settings
    private const XML_PATH_GUARD_ENABLED = 'magesetu_semantic_search/guardrails/enabled';
    private const XML_PATH_GUARD_PROVIDER = 'magesetu_semantic_search/guardrails/provider';
    private const XML_PATH_GUARD_SYSTEM_PROMPT = 'magesetu_semantic_search/guardrails/guard_system_prompt';
    private const XML_PATH_GUARD_REASONING_EFFORT = 'magesetu_semantic_search/guardrails/reasoning_effort';
    private const XML_PATH_GUARD_MAX_TOKENS = 'magesetu_semantic_search/guardrails/max_tokens';
    private const XML_PATH_GUARD_ERROR_MESSAGE = 'magesetu_semantic_search/guardrails/guard_error_msg';
    private const XML_PATH_GUARD_CACHE_LIFETIME = 'magesetu_semantic_search/guardrails/cache_lifetime';

    // Vector database Settings
    private const XML_PATH_VECTOR_DB_STORAGE = 'magesetu_semantic_search/vectordb/storage';
    private const XML_PATH_VECTOR_DB_SEARCH_LIMIT = 'magesetu_semantic_search/vectordb/search_limit';
    private const XML_PATH_VECTOR_DB_SCORE_THRESHOLD = 'magesetu_semantic_search/vectordb/score_threshold';

    // OpenSearch connection settings
    private const XML_PATH_VD_OS_URL = 'magesetu_semantic_search/vectordb/opensearch_server_url';
    private const XML_PATH_VD_OS_ENABLE_AUTH = 'magesetu_semantic_search/vectordb/opensearch_enable_auth';
    private const XML_PATH_VD_OS_USERNAME = 'magesetu_semantic_search/vectordb/opensearch_username';
    private const XML_PATH_VD_OS_PASSWORD = 'magesetu_semantic_search/vectordb/opensearch_password';
    private const XML_PATH_VD_OS_TIMEOUT = 'magesetu_semantic_search/vectordb/opensearch_server_timeout';

    // Qdrant connection settings
    private const XML_PATH_QDRANT_API_URL = 'magesetu_semantic_search/vectordb/qdrant_api_url';
    private const XML_PATH_QDRANT_API_TOKEN = 'magesetu_semantic_search/vectordb/qdrant_api_token';

    // Personalized Message LLM Settings
    private const XML_PATH_PRS_MSG_ENABLED = 'magesetu_semantic_search/personalized_message/enabled';
    private const XML_PATH_PRS_MSG_PROVIDER = 'magesetu_semantic_search/personalized_message/provider';
    private const XML_PATH_PRS_MSG_SYSTEM_CONTEXT = 'magesetu_semantic_search/personalized_message/system_context';
    private const XML_PATH_PRS_MSG_TEMPERATURE = 'magesetu_semantic_search/personalized_message/temperature';
    private const XML_PATH_PRS_MSG_MAX_TOKENS = 'magesetu_semantic_search/personalized_message/max_tokens';

    private const CONFIG_AI_GROUPS = ['embedding_llm', 'guardrails', 'personalized_message'];
    private const AI_PROVIDERS = ['openai', 'ollama'];

    /**
     * Class constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isModuleEnabled(null|int|string $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getSemanticAttributes(null|int|string $scopeCode = null): array
    {
        $attributes = (string) $this->scopeConfig->getValue(
            self::XML_PATH_SEMANTIC_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        return $attributes ? explode(",", $attributes) : [];
    }

    /**
     * @inheritDoc
     */
    public function getIndexBatchSize(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_INDEX_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getEmbeddingBatchSize(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_EMBEDDING_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getEmbeddingProvider(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMBEDDING_PROVIDER,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getEmbeddingCacheLifetime(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_EMBEDDING_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function isGuardrailsEnabled(null|int|string $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GUARD_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuardrailsProvider(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GUARD_PROVIDER,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuardSystemPrompt(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GUARD_SYSTEM_PROMPT,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuardErrorMessage(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GUARD_ERROR_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuardReasoningEffort(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GUARD_REASONING_EFFORT,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuardMaxTokens(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_GUARD_MAX_TOKENS,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getGuardCacheLifetime(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_GUARD_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getVectorDbStorage(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_VECTOR_DB_STORAGE
        );
    }

    /**
     * @inheritDoc
     */
    public function getVectorDbSearchLimit(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_VECTOR_DB_SEARCH_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getVectorDbScoreThreshold(null|int|string $scopeCode = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_VECTOR_DB_SCORE_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getOpenSearchUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_VD_OS_URL
        );
    }

    /**
     * @inheritDoc
     */
    public function isOpenSearchAuthEnabled(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(
            self::XML_PATH_VD_OS_ENABLE_AUTH
        );
    }

    /**
     * @inheritDoc
     */
    public function getOpenSearchUsername(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_VD_OS_USERNAME
        );
    }

    /**
     * @inheritDoc
     */
    public function getOpenSearchPassword(): string
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_VD_OS_PASSWORD
        );

        return $this->encryptor->decrypt($value);
    }

    /**
     * @inheritDoc
     */
    public function getOpenSearchTimeout(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_VD_OS_TIMEOUT
        );
    }

    /**
     * @inheritDoc
     */
    public function getQdrantApiUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_QDRANT_API_URL
        );
    }

    /**
     * @inheritDoc
     */
    public function getQdrantApiToken(): string
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_QDRANT_API_TOKEN
        );

        return $this->encryptor->decrypt($value);
    }

    /**
     * @inheritDoc
     */
    public function isPersonalizedMessageEnabled(null|int|string $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRS_MSG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getPersonalizedMessageProvider(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRS_MSG_PROVIDER,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getPersonalizedMessageSystemContext(null|int|string $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRS_MSG_SYSTEM_CONTEXT,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getPersonalizedMessageTemperature(null|int|string $scopeCode = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_PRS_MSG_TEMPERATURE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getPersonalizedMessageMaxTokens(null|int|string $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PRS_MSG_MAX_TOKENS,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getAiModelOptions(
        string $group,
        string $provider,
        null|int|string $scopeCode = null
    ): array {
        if (
            !in_array($group, self::CONFIG_AI_GROUPS) || !in_array($provider, self::AI_PROVIDERS)
        ) {
            return [];
        }

        $paths = $this->resolveAiModelPaths($group, $provider);

        $model = (string) $this->scopeConfig->getValue(
            $paths['model'],
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        $apiKey = (string) $this->scopeConfig->getValue(
            $paths['apiKey'],
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
        $apiKey = $this->encryptor->decrypt($apiKey);

        $baseUrl = (string) $this->scopeConfig->getValue(
            $paths['baseUrl'],
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        ) ?? null;

        return [
            'model' => $model,
            'apiKey' => $apiKey,
            'baseUrl' => $baseUrl
        ];
    }

    /**
     * Ai model config path resolver
     *
     * @param string $group
     * @param string $provider
     * @return array
     */
    private function resolveAiModelPaths(string $group, string $provider): array
    {
        return [
            'model' => sprintf("%s/%s/%s_model", self::XML_PATH_SECTION, $group, $provider),
            'apiKey' => sprintf("%s/%s/%s_api_key", self::XML_PATH_SECTION, $group, $provider),
            'baseUrl' => sprintf("%s/%s/%s_base_url", self::XML_PATH_SECTION, $group, $provider)
        ];
    }
}
