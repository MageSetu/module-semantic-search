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

use MageSetu\SemanticSearch\Api\SemanticSearchInterface;
use MageSetu\SemanticSearch\Api\Data\SemanticSearchResponseInterfaceFactory;
use MageSetu\SemanticSearch\Api\Data\SemanticSearchResponseInterface;
use MageSetu\SemanticSearch\Model\Service\SearchQueryValidator;
use MageSetu\SemanticSearch\Model\Service\ProductDetailsProvider;
use Magento\Store\Model\StoreManagerInterface;
use MageSetu\SemanticSearch\Model\Service\PersonalizedMessageService;
use MageSetu\SemanticSearch\Model\Service\VectorSearchService;
use MageSetu\SemanticSearch\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Semantic Search Model
 * @api
 * @since 1.0.0
 */
class SemanticSearch implements SemanticSearchInterface
{
    /**
     * Class constructor
     *
     * @param SemanticSearchResponseInterfaceFactory $semanticSearchResponseFactory
     * @param SearchQueryValidator $searchQueryValidator
     * @param ProductDetailsProvider $productDetailsProvider
     * @param StoreManagerInterface $storeManager
     * @param PersonalizedMessageService $personalizedMessageService
     * @param VectorSearchService $vectorSearchService
     * @param Logger $logger
     */
    public function __construct(
        private readonly SemanticSearchResponseInterfaceFactory $semanticSearchResponseFactory,
        private readonly SearchQueryValidator $searchQueryValidator,
        private readonly ProductDetailsProvider $productDetailsProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly PersonalizedMessageService $personalizedMessageService,
        private readonly VectorSearchService $vectorSearchService,
        private readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function search(string $query, ?int $storeId = null): SemanticSearchResponseInterface
    {
        /** @var SemanticSearchResponseInterface $response */
        $response = $this->semanticSearchResponseFactory->create();

        $storeId = $storeId ?? (int) $this->storeManager->getStore()->getId();

        try {
            // Validate user query with guardrails and sanitize the value
            $query = $this->searchQueryValidator->validate($query, $storeId);

            // Get product ids from vector database matching user query
            $productIds = $this->vectorSearchService->fetchProducts($query, $storeId);

            // Get product details to display on the front
            $products = $this->productDetailsProvider->getDetailsByIdsForStore($productIds, $storeId);

            $response->setProducts($products);
            $response->setMessage(
                $this->personalizedMessageService->generateMessage($query, $storeId, $products)
            );

            $response->setStatus($products ? true : false);
        } catch (LocalizedException $e) {
            $response->setMessage($e->getMessage());
            $response->setStatus(false);
        } catch (\Exception $e) {
            $this->logger->error(
                "[SemanticSearch] Error while searching the products",
                ['query' => $query, 'store_id' => $storeId, 'exception' => $e]
            );

            $response->setMessage(__("Unable to serve the request at the moment.")->render());
            $response->setStatus(false);
        }

        return $response;
    }
}
