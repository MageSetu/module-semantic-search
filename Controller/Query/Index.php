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

namespace MageSetu\SemanticSearch\Controller\Query;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use MageSetu\SemanticSearch\Api\SemanticSearchInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index implements HttpPostActionInterface
{
    /**
     * Class constructor
     *
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param SemanticSearchInterface $searchService
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly SemanticSearchInterface $searchService,
        private readonly ConfigInterface $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Execute post request
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isModuleEnabled($storeId)) {
            return $result->setHttpResponseCode(404);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setHttpResponseCode(401);
        }

        $query = $this->getQuery();

        if (!$query) {
            return $result->setHttpResponseCode(400)->setData([
                'message' => __("Query cannot be empty.")
            ]);
        }

        if (strlen($query) > 500) {
            return $result->setHttpResponseCode(400)->setData([
                'message' => __("Search query too long. Please try query under 500 characters.")
            ]);
        }

        $searchResult = $this->searchService->search($query);

        if (!$searchResult->isSuccess()) {
            return $result->setHttpResponseCode(200)->setData([
                'success' => false,
                'query' => $query,
                'message' => $searchResult->getMessage()
            ]);
        }

        $products = array_map(
            fn($product) => $product->getData(),
            $searchResult->getProducts()
        );

        return $result->setHttpResponseCode(200)->setData([
            'success' => true,
            'query' => $query,
            'result' => [
                'message' => $searchResult->getMessage(),
                'products' => $products
            ]
        ]);
    }

    /**
     * Get search query from GET or POST
     */
    protected function getQuery(): string
    {
        $query = (string) $this->request->getParam('query', '');

        return trim($query);
    }
}
