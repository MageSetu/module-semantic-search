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

namespace MageSetu\SemanticSearch\Block\Adminhtml\System\Config\Guardrails;

use MageSetu\SemanticSearch\Block\Adminhtml\System\Config\AbstractTestConnection;

class TestConnection extends AbstractTestConnection
{
    /**
     * @inheritDoc
     */
    protected function getPostUrl(): string
    {
        return $this->_urlBuilder->getUrl('magesetu_semanticsearch/systemConfig/testGuardrailsConnection');
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMapping(): array
    {
        return [
            'provider' => 'magesetu_semantic_search_guardrails_provider',
            'openai_model' => 'magesetu_semantic_search_guardrails_openai_model',
            'openai_api_key' => 'magesetu_semantic_search_guardrails_openai_api_key',
            'openai_base_url' => 'magesetu_semantic_search_guardrails_openai_base_url',
            'ollama_model' => 'magesetu_semantic_search_guardrails_ollama_model',
            'ollama_base_url' => 'magesetu_semantic_search_guardrails_ollama_base_url',
        ];
    }
}
