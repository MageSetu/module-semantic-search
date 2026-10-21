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
use MageSetu\Common\Http\Client\CurlFactory;
use MageSetu\Common\Http\Client\Curl;
use Magento\Framework\Filter\StripTags;
use MageSetu\SemanticSearch\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

class TestQdrantConnection extends Action implements HttpPostActionInterface
{
    /** cURL Connection timeout - seconds to connect */
    protected const CURL_CONNECTION_TIMEOUT = 10;

    /** cURL timeout */
    protected const CURL_TIMEOUT = 60;

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
     * @param CurlFactory $curlFactory
     * @param StripTags $tagFilter
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CurlFactory $curlFactory,
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

        $params = $this->getRequest()->getParams();

        $url = sprintf("%s/readyz", $params['api_url']);
        $authHeader = $params['api_token'] ? ['api-key' => $params['api_token']] : [];

        try {
            $this->testConnection($url, $authHeader);

            // /readyz returns a plain "healthz check passed" style body, not the
            // usual {"status": "ok", ...} envelope used by collection endpoints.
            // Treat any successful (non-throwing) response as "connected".

            $result['success'] = true;
            $this->logger->debug("[ConfigTest] Qdrant connection test was successful.");
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $result['errorMessage'] = $this->tagFilter->filter($e->getMessage());
            $this->logger->error(
                '[ConfigTest] Qdrant connection test failed',
                ['error' => $e->getMessage()]
            );
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }

    /**
     * Tests connectivity to the Qdrant instance without touching any collection.
     *
     * @param string $url
     * @param array $authHeader
     * @return void
     */
    private function testConnection(string $url, array $authHeader): void
    {
        /** @var Curl $curl */
        $curl = $this->curlFactory->create();

        $curl->setOption(CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTION_TIMEOUT);
        $curl->setOption(CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
        $curl->setHeaders($authHeader);

        $curl->get($url);

        $status = $curl->getStatus();

        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(
                __('Test failed. Unexpected HTTP %1 from %2', $status, $url)
            );
        }
    }
}
