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

use MageSetu\SemanticSearch\Model\ResourceModel\Product as SemanticSearchProductResource;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\AiBase\Api\AiClientOptionsProviderInterface;
use MageSetu\SemanticSearch\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;

class PayloadProcessor
{
    /**
     * Class constructor
     *
     * @param SemanticSearchProductResource $semanticSearchProductResource
     * @param ConfigInterface $config
     * @param AiClientOptionsProviderInterface[] $embeddingClientOptionsProviders
     */
    public function __construct(
        private readonly SemanticSearchProductResource $semanticSearchProductResource,
        private readonly ConfigInterface $config,
        private readonly array $embeddingClientOptionsProviders = []
    ) {
    }

    /**
     * Prepare bulk DB insert data and message queue payloads by validating hashes
     *
     * @param array $productsToUpdate
     * @param int $storeId
     * @return array array of array with pending items data
     * @throws LocalizedException
     */
    public function prepareBulkPendingPayloads(array $productsToUpdate, int $storeId): array
    {
        $productIds = array_column($productsToUpdate, 'product_id');

        $provider = $this->config->getEmbeddingProvider($storeId);

        $optionsProvider = $this->embeddingClientOptionsProviders[$provider] ?? null;
        if (!$optionsProvider instanceof AiClientOptionsProviderInterface) {
            throw new LocalizedException(
                __('Embedding client options provider not found for provider "%1".', $provider)
            );
        }

        $options = $optionsProvider->getOptions($storeId);
        if (!isset($options['model'])) {
            throw new LocalizedException(__('Embedding model not found for provider "%1".', $provider));
        }

        $embeddingModel = $options['model'];
        $storage = $this->config->getVectorDbStorage();

        $existingData = $this->semanticSearchProductResource->getExistingHashesForBatch(
            $productIds,
            $storeId
        );

        $bulkInsertData = [];
        $messageQueueProducts = [];

        foreach ($productsToUpdate as $product) {
            $productId = (int) $product['product_id'];
            $semanticAttrStr = http_build_query($product['semantic_attributes']);
            $hashContent = implode(" ", [$provider, $embeddingModel, $storage, $semanticAttrStr]);
            $hash = hash('sha256', $hashContent);

            $needsDbUpdate = false;
            $needsQueueUpdate = false;

            if (array_key_exists($productId, $existingData)) {
                if ($hash !== $existingData[$productId][ProductInterface::CONTENT_HASH]) {
                    $needsDbUpdate = true;
                    $needsQueueUpdate = true;
                } elseif (
                    (int) $existingData[$productId][ProductInterface::EMBEDDING_STATUS]
                    !== ProductInterface::EMBEDDING_STATUS_SUCCESS
                ) {
                    $needsQueueUpdate = true;
                }
            } else {
                $needsDbUpdate = true;
                $needsQueueUpdate = true;
            }

            if ($needsDbUpdate) {
                $bulkInsertData[] = [
                    ProductInterface::PRODUCT_ID => $productId,
                    ProductInterface::STORE_ID => $storeId,
                    ProductInterface::CONTENT_HASH => $hash,
                    ProductInterface::EMBEDDING_STATUS => ProductInterface::EMBEDDING_STATUS_PENDING
                ];
            }

            if ($needsQueueUpdate) {
                $messageQueueProducts[] = $product;
            }
        }

        return [$bulkInsertData, $messageQueueProducts];
    }
}
