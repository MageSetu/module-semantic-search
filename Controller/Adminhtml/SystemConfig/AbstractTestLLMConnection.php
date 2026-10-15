<?php
/**
 * Copyright (c) 2025 MageSetu. All rights reserved.
 *
 * @package    MageSetu_SemanticSearch
 * @author     MageSetu
 * @copyright  Copyright (c) 2025 MageSetu
 * @license    https://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link       https://github.com/MageSetu/module-semantic-search
 */

declare(strict_types=1);

namespace MageSetu\SemanticSearch\Controller\Adminhtml\SystemConfig;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use MageSetu\AiBase\Api\AiClientPoolInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Filter\StripTags;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\AiBase\Api\AiClientInterface;
use MageSetu\AiBase\Exception\AiClientException;
use Magento\Framework\Exception\LocalizedException;

abstract class AbstractTestLLMConnection extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'MageSetu_SemanticSearch::config';

    /**
     * Class constructor
     *
     * @param Context $context
     * @param AiClientPoolInterface $aiClientPool
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        protected readonly AiClientPoolInterface $aiClientPool,
        protected readonly JsonFactory $resultJsonFactory,
        protected readonly StripTags $tagFilter,
        protected readonly Logger $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Check for connection to server
     *
     * @return Json
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];

        try {
            $client = $this->getClient();
            if ($client->isAvailable()) {
                $result['success'] = true;
                $this->logger->debug(
                    sprintf("[ConfigTest] Connection test with %s was successful.", $client->getLabel())
                );
            }
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $result['errorMessage'] = $this->tagFilter->filter($e->getMessage());
            $this->logger->error(
                '[ConfigTest] LLM connection test failed',
                ['error' => $e->getMessage()]
            );
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }

    /**
     * Get client from the request
     *
     * @return AiClientInterface
     * @throws LocalizedException
     */
    protected function getClient(): AiClientInterface
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['provider'])) {
            throw new LocalizedException(
                __('Provider cannot be empty.')
            );
        }

        $provider = $params['provider'];

        $options = match ($provider) {
            'openai' => [
                'baseUrl' => $params['openai_base_url'] ?? '',
                'model' => $params['openai_model'] ?? '',
                'apiKey' => $params['openai_api_key'] ?? '',
            ],
            'ollama' => [
                'baseUrl' => $params['ollama_base_url'] ?? '',
                'model' => $params['ollama_model'] ?? '',
            ]
        };

        if (empty($options['baseUrl'])) {
            unset($options['baseUrl']);
        }

        try {
            return $this->aiClientPool->create($provider, $options);
        } catch (AiClientException $e) {
            throw new LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error(
                "[ConfigTest] client creation failed.",
                [
                    'error' => $e->getMessage(),
                    'provider' => $provider,
                    'options' => $options
                ]
            );

            throw new LocalizedException(
                __("Something went wrong. Please check logs for more details.")
            );
        }
    }
}
