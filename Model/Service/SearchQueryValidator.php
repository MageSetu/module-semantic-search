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

use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\AiBase\Api\AiClientProviderInterface;
use MageSetu\AiBase\Api\Data\StructuredOutputInterfaceFactory;
use MageSetu\AiBase\Api\Data\StructuredOutputInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use Magento\Framework\Escaper;
use MageSetu\SemanticSearch\Model\Cache\Service\GuardrailsCacheService;
use Magento\Framework\Serialize\SerializerInterface;
use MageSetu\AiBase\Api\ChatClientInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Helpful service to validate user query
 *
 * @api
 * @since 1.0.0
 */
class SearchQueryValidator
{
    /**
     * Class constructor
     *
     * @param ConfigInterface $config
     * @param AiClientProviderInterface $guardrailClientProvider
     * @param StructuredOutputInterfaceFactory $structuredOutputFactory
     * @param Logger $logger
     * @param Escaper $escaper
     * @param GuardrailsCacheService $cacheService
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly AiClientProviderInterface $guardrailClientProvider,
        private readonly StructuredOutputInterfaceFactory $structuredOutputFactory,
        private readonly Logger $logger,
        private readonly Escaper $escaper,
        private readonly GuardrailsCacheService $cacheService,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Validate query
     *
     * Returns sanitized query
     *
     * @param string $query
     * @param int $storeId
     * @return string - sanitized query
     * @throws LocalizedException
     */
    public function validate(string $query, int $storeId): string
    {
        if (!$query) {
            throw new LocalizedException(__("Query cannot be empty."));
        }

        $query = $this->escaper->escapeHtml($query);

        if (!$this->config->isGuardrailsEnabled($storeId)) {
            return $query;
        }

        $cacheKey = $this->cacheService->buildCacheKey($query, $storeId);
        $cached = $this->cacheService->loadFromCache($cacheKey);

        if ($cached) {
            if ($cached['is_valid'] === true) {
                return $query;
            } else {
                throw new LocalizedException(__($this->getInvalidQueryMessage()));
            }
        }

        $isValid = $this->validateViaApi($query, $storeId);

        $this->cacheService->saveValidityToCache($cacheKey, $isValid, $storeId);

        if (!$isValid) {
            throw new LocalizedException(__($this->getInvalidQueryMessage()));
        }

        return $query;
    }

    /**
     * Validate query
     *
     * @param string $query
     * @param int $storeId
     * @return bool
     * @throws LocalizedException
     */
    protected function validateViaApi(string $query, int $storeId): bool
    {
        $provider = $this->config->getGuardrailsProvider($storeId);
        $systemInstruction = $this->config->getGuardSystemPrompt($storeId);
        $prompt = "<user_input>{$query}</user_input>";
        $outputFormat = $this->getOutputFormat();
        $options = [
            'max_output_tokens' => $this->config->getGuardMaxTokens($storeId),
            'temperature' => 0,
            'reasoning_effort' => $this->config->getGuardReasoningEffort($storeId)
        ];

        try {
            /** @var ChatClientInterface $client */
            $client = $this->guardrailClientProvider->getClient($storeId, $provider);

            $response = $client->complete($prompt, $systemInstruction, $options, $outputFormat);
            $responseArr = $this->serializer->unserialize($response);
            $status = $responseArr['verdict'] ?? "block";
        } catch (\Exception $e) {
            $this->logger->error(
                "[SemanticSearch] Failed to validate the user query.",
                [
                    'error' => $e->getMessage(),
                    'store_id' => $storeId,
                    'provider' => $provider
                ]
            );

            throw new LocalizedException(__("Unable to serve the request at the moment."));
        }

        return $status === "allow";
    }

    /**
     * Default error message when the query is invalid.
     */
    protected function getInvalidQueryMessage()
    {
        return $this->config->getGuardErrorMessage();
    }

    /**
     * Get model output format
     *
     * @return StructuredOutputInterface
     */
    private function getOutputFormat(): StructuredOutputInterface
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'verdict' => [
                    'type' => 'string',
                    'enum' => ['allow', 'block']
                ]
            ],
            'required' => ['verdict'],
            'additionalProperties' => false
        ];

        $structuredOut = $this->structuredOutputFactory->create();
        $structuredOut->setName('user_query_guardrail');
        $structuredOut->setIsStrict(true);
        $structuredOut->setSchema($schema);

        return $structuredOut;
    }
}
