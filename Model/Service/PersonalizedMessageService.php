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
use MageSetu\SemanticSearch\Logger\Logger;
use Magento\Framework\Escaper;
use MageSetu\SemanticSearch\Model\Util\MarkdownProcessor;
use MageSetu\SemanticSearch\Api\Data\SearchResultProductInterface;
use MageSetu\AiBase\Api\ChatClientInterface;

class PersonalizedMessageService
{
    /**
     * Class constructor
     *
     * @param ConfigInterface $config
     * @param AiClientProviderInterface $aiClientProvider
     * @param Logger $logger
     * @param Escaper $escaper
     * @param MarkdownProcessor $markdownProcessor
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly AiClientProviderInterface $aiClientProvider,
        private readonly Logger $logger,
        private readonly Escaper $escaper,
        private readonly MarkdownProcessor $markdownProcessor
    ) {
    }

    /**
     * Generate message for the response when products found.
     *
     * @param string $query
     * @param int $storeId
     * @param SearchResultProductInterface[] $products
     * @return string
     */
    public function generateMessage(string $query, int $storeId, array $products): string
    {
        if ($products) {
            $defaultMessage = __("Based on your query, here are some products recommended.")->render();
        } else {
            $defaultMessage = __("No products found.")->render();
        }

        if (!$this->config->isPersonalizedMessageEnabled($storeId)) {
            return $defaultMessage;
        }

        $provider = $this->config->getPersonalizedMessageProvider($storeId);

        try {
            /** @var ChatClientInterface $client */
            $client = $this->aiClientProvider->getClient($storeId, $provider);
        } catch (\Exception $e) {
            $this->logger->error(
                "[SemanticSearch] Error while getting the personalized message client.",
                [
                    'error' => $e->getMessage(),
                    "store_id" => $storeId
                ]
            );

            return $defaultMessage;
        }

        $productContext = "";
        foreach ($products as $product) {
            $description = trim(strip_tags((string) $product->getDescription()));
            $productContext .= "Product Details: \n";
            $productContext .= "Name: {$product->getName()}\n";
            $productContext .= "Description: {$description}\n";
            $productContext .= "---\n";
        }

        if ($productContext === "") {
            $productContext = "No matching products found.";
        }

        $messages = $this->getMessages($query, $productContext, $storeId);
        $options = [
            'max_output_tokens' => $this->config->getPersonalizedMessageMaxTokens($storeId),
            'temperature' => $this->config->getPersonalizedMessageTemperature($storeId),
            'reasoning_effort' => 'none'
        ];

        try {
            $output = $client->chat($messages, $options);
            $output = $this->escaper->escapeHtml($output);
            $output = str_replace(['&#039;', '&apos;'], "'", $output); // allow single quotes
            $output = $this->markdownProcessor->markdownToHtml($output);

            return $output;
        } catch (\Exception $e) {
            $this->logger->error(
                "[SemanticSearch] Failed to generate personalized message.",
                [
                    'error' => $e->getMessage(),
                    "store_id" => $storeId
                ]
            );

            return $defaultMessage;
        }
    }

    /**
     * Generate prompt messages to get personalized response
     *
     * @param  string $query
     * @param  string $productContext
     * @param  int    $storeId
     * @return array
     */
    private function getMessages(string $query, string $productContext, int $storeId): array
    {
        $systemInstruction = $this->config->getPersonalizedMessageSystemContext($storeId);

        $userMessage =
            "<user_input>{$query}</user_input>" .
            "<recommended_products>{$productContext}</recommended_products>";

        return [
            ['role' => 'system', 'content' => $systemInstruction],
            ['role' => 'user', 'content' => $userMessage],
        ];
    }
}
