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

namespace MageSetu\SemanticSearch\Block\Adminhtml\System\Config\VectorStorage;

use MageSetu\SemanticSearch\Block\Adminhtml\System\Config\AbstractTestConnection;

class TestQdrantConnection extends AbstractTestConnection
{
    /**
     * Path to template file in theme.
     * @var string
     *
     * @inheritDoc
     */
    protected $_template = 'MageSetu_SemanticSearch::system/config/vectorstorage/test_connection.phtml';

    /**
     * @inheritDoc
     */
    protected function getPostUrl(): string
    {
        return $this->_urlBuilder->getUrl('magesetu_semanticsearch/systemConfig/testQdrantConnection');
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMapping(): array
    {
        return [
            'api_url' => 'magesetu_semantic_search_vectordb_qdrant_api_url',
            'api_token' => 'magesetu_semantic_search_vectordb_qdrant_api_token',
        ];
    }
}
