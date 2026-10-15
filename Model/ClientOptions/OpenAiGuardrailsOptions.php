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

namespace MageSetu\SemanticSearch\Model\ClientOptions;

use MageSetu\AiBase\Api\AiClientOptionsProviderInterface;
use MageSetu\SemanticSearch\Api\ConfigInterface;

class OpenAiGuardrailsOptions implements AiClientOptionsProviderInterface
{
    /**
     * Class constructor
     *
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOptions(int $storeId): array
    {
        return $this->config->getAiModelOptions('guardrails', 'openai');
    }
}
