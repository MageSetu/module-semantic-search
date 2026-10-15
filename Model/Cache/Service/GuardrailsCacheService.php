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

namespace MageSetu\SemanticSearch\Model\Cache\Service;

use MageSetu\SemanticSearch\Model\Cache\Type\SearchResult as SearchResultCache;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;

class GuardrailsCacheService
{
    private const CACHE_KEY_PREFIX = 'magesetu_semanticsearch_guardrails';

    private const CACHE_TAG_STORE_FMT = 'magesetu_semanticsearch_guardrails_store_%d';

    private const CACHE_LIFETIME = 86400; // 1 day; tag-based flush handles freshness on change

    /**
     * Class constructor
     *
     * @param SearchResultCache $cache
     * @param ConfigInterface $config
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly SearchResultCache $cache,
        private readonly ConfigInterface $config,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Generate cache key from the user query
     *
     * @param string $query
     * @param int $storeId
     * @return string
     */
    public function buildCacheKey(string $query, int $storeId): string
    {
        $hash = hash('sha256', preg_replace('/\s+/', ' ', mb_strtolower(trim($query))));

        return sprintf("%s_%d_%s", self::CACHE_KEY_PREFIX, $storeId, $hash);
    }

    /**
     * Load data from the cache
     *
     * @param string $key
     * @return array
     */
    public function loadFromCache(string $key): array
    {
        $cached = $this->cache->load($key);

        if (!$cached) {
            return [];
        }

        return $this->serializer->unserialize($cached);
    }

    /**
     * Save query validity to cache
     *
     * @param string $key
     * @param bool $isValid
     * @param int $storeId
     * @return void
     */
    public function saveValidityToCache(string $key, bool $isValid, int $storeId): void
    {
        $this->cache->save(
            $this->serializer->serialize(['is_valid' => $isValid]),
            $key,
            [SearchResultCache::CACHE_TAG, sprintf(self::CACHE_TAG_STORE_FMT, $storeId)],
            $this->getCacheLifetime($storeId)
        );
    }

    /**
     * Clean cache for a particular store
     *
     * @param int $storeId
     * @return void
     */
    public function cleanCacheForStoreId(int $storeId): void
    {
        $this->cache->clean(
            \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            [sprintf(self::CACHE_TAG_STORE_FMT, $storeId)]
        );
    }

    /**
     * Get cache lifetime
     *
     * @param int $storeId
     * @return int
     */
    private function getCacheLifetime(int $storeId): int
    {
        return $this->config->getGuardCacheLifetime($storeId) ?? self::CACHE_LIFETIME;
    }
}
