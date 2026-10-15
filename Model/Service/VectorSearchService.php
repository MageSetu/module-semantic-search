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

namespace MageSetu\SemanticSearch\Model\Service;

use MageSetu\AiBase\Api\AiClientProviderInterface;
use MageSetu\SemanticSearch\Api\VectorStorageProviderInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService;
use MageSetu\AiBase\Api\EmbeddingClientInterface;
use MageSetu\SemanticSearch\Api\VectorStorageInterface;

class VectorSearchService
{
    /**
     * Class constructor
     *
     * @param AiClientProviderInterface $embeddingClientProvider
     * @param VectorStorageProviderInterface $storageProvider
     * @param ConfigInterface $config
     * @param EmbeddingCacheService $cacheService
     */
    public function __construct(
        private readonly AiClientProviderInterface $embeddingClientProvider,
        private readonly VectorStorageProviderInterface $storageProvider,
        private readonly ConfigInterface $config,
        private readonly EmbeddingCacheService $cacheService
    ) {
    }

    /**
     * Fetch matching products
     *
     * @param string $query
     * @param int $storeId
     * @return array
     */
    public function fetchProducts(string $query, int $storeId): array
    {
        $cacheKey = $this->cacheService->buildCacheKey($query, $storeId);
        $cached = $this->cacheService->loadFromCache($cacheKey);

        if ($cached) {
            return $cached['product_ids'] ?? [];
        }

        $productIds = $this->fetchFromApi($query, $storeId);

        $this->cacheService->saveProductIdsToCache($cacheKey, $productIds, $storeId);

        return $productIds;
    }

    /**
     * Fetch matching items from vector DB via API
     *
     * @param string $query
     * @param int $storeId
     * @return array
     */
    protected function fetchFromApi(string $query, int $storeId): array
    {
        $embeddingProvider = $this->config->getEmbeddingProvider($storeId);

        /** @var EmbeddingClientInterface $embeddingClient */
        $embeddingClient = $this->embeddingClientProvider->getClient($storeId, $embeddingProvider);

        /** @var VectorStorageInterface $vectorStorage */
        $vectorStorage = $this->storageProvider->getStorageClient();

        $searchLimit = $this->config->getVectorDbSearchLimit($storeId);
        $scoreThreshold = $this->config->getVectorDbScoreThreshold($storeId);

        $embeddings = $embeddingClient->generateEmbedding($query);

        $response = $vectorStorage->searchProducts(
            $storeId,
            $embeddings,
            $searchLimit,
            $scoreThreshold
        );

        return array_column($response, 'product_id');
    }
}
