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

class EmbeddingCacheService
{
    private const CACHE_KEY_PREFIX = 'magesetu_semanticsearch_embedding';

    private const CACHE_TAG_STORE_FMT = 'magesetu_semanticsearch_embedding_store_%d';

    private const CACHE_TAG_PRODUCT_FMT = 'magesetu_semanticsearch_embedding_product_%d';

    private const CACHE_TAG_STORE_PRODUCT_FMT = 'magesetu_semanticsearch_embedding_store_%d_product_%d';

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
     * Save product ids to cache
     *
     * @param string $key
     * @param array $productIds
     * @param int $storeId
     * @return void
     */
    public function saveProductIdsToCache(string $key, array $productIds, int $storeId): void
    {
        $this->cache->save(
            $this->serializer->serialize(['product_ids' => $productIds]),
            $key,
            $this->buildTags($storeId, $productIds),
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
     * Product deleted/unassigned — wipe it everywhere, all stores.
     *
     * @param int $productId
     * @return void
     */
    public function cleanCacheForProduct(int $productId): void
    {
        $this->cache->clean(
            \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            [sprintf(self::CACHE_TAG_PRODUCT_FMT, $productId)]
        );
    }

    /**
     * Common case: one product re-embedded in one store view.
     *
     * @param int $productId
     * @param int $storeId
     * @return void
     */
    public function cleanCacheProductInStore(int $productId, int $storeId): void
    {
        $this->cache->clean(
            \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            [sprintf(self::CACHE_TAG_STORE_PRODUCT_FMT, $storeId, $productId)]
        );
    }

    /**
     * Batch variant for full multi-store reindex.
     *
     * @param array $productIdsByStoreId array<storeId, productIds>
     * @return void
     */
    public function cleanCacheForProductsInStores(array $productIdsByStoreId): void
    {
        $tags = [];
        foreach ($productIdsByStoreId as $storeId => $productIds) {
            foreach ($productIds as $productId) {
                $tags[] = sprintf(self::CACHE_TAG_STORE_PRODUCT_FMT, (int) $storeId, (int) $productId);
            }
        }

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array_unique($tags));
    }

    /**
     * Get cache lifetime
     *
     * @param int $storeId
     * @return int
     */
    private function getCacheLifetime(int $storeId): int
    {
        return $this->config->getEmbeddingCacheLifetime($storeId) ?? self::CACHE_LIFETIME;
    }

    /**
     * Build the full tag set for a cached query result.
     *
     * @param int   $storeId
     * @param int[] $productIds
     * @return string[]
     */
    private function buildTags(int $storeId, array $productIds): array
    {
        $tags = [
            SearchResultCache::CACHE_TAG,
            sprintf(self::CACHE_TAG_STORE_FMT, $storeId),
        ];

        foreach ($productIds as $productId) {
            $productId = (int) $productId;
            $tags[] = sprintf(self::CACHE_TAG_PRODUCT_FMT, $productId);
            $tags[] = sprintf(self::CACHE_TAG_STORE_PRODUCT_FMT, $storeId, $productId);
        }

        return array_unique($tags);
    }
}
