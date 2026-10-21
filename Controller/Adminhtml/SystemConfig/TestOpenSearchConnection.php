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

namespace MageSetu\SemanticSearch\Controller\Adminhtml\SystemConfig;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Filter\StripTags;
use MageSetu\SemanticSearch\Logger\Logger;
use OpenSearch\GuzzleClientFactory;
use OpenSearch\Client;
use Magento\Framework\Exception\LocalizedException;

class TestOpenSearchConnection extends Action implements HttpPostActionInterface
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
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly StripTags $tagFilter,
        private readonly Logger $logger
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
            $response = $this->getClient()->request('GET', '/');
            if (
                isset($response['version']['distribution'])
                && $response['version']['distribution'] === 'opensearch'
            ) {
                $result['success'] = true;
                $this->logger->debug("[ConfigTest] OpenSearch connection test was successful.");
            }
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $result['errorMessage'] = $this->tagFilter->filter($e->getMessage());
            $this->logger->error(
                '[ConfigTest] OpenSearch connection test failed',
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
     * @return Client
     * @throws LocalizedException
     */
    protected function getClient(): Client
    {
        $params = $this->getRequest()->getParams();

        $clientConfig = [
            'base_uri' => $params['server_url'],
            'timeout' => $params['timeout']
        ];

        if ($params['is_auth_enabled']) {
            $clientConfig['auth'] = [$params['username'], $params['password']];
        }

        try {
            $factory = new GuzzleClientFactory();
            return $factory->create($clientConfig);
        } catch (\Exception $e) {
            $this->logger->error(
                '[ConfigTest] OpenSearch client creation failed',
                ['error' => $e->getMessage()]
            );

            throw new LocalizedException(
                __('Unable to initialize OpenSearch client.')
            );
        }
    }
}
