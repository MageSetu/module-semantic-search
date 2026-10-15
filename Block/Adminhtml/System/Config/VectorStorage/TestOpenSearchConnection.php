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

namespace MageSetu\SemanticSearch\Block\Adminhtml\System\Config\VectorStorage;

use MageSetu\SemanticSearch\Block\Adminhtml\System\Config\AbstractTestConnection;

class TestOpenSearchConnection extends AbstractTestConnection
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
        return $this->_urlBuilder->getUrl('magesetu_semanticsearch/systemConfig/testOpenSearchConnection');
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMapping(): array
    {
        return [
            'server_url' => 'magesetu_semantic_search_vectordb_opensearch_server_url',
            'is_auth_enabled' => 'magesetu_semantic_search_vectordb_opensearch_enable_auth',
            'username' => 'magesetu_semantic_search_vectordb_opensearch_username',
            'password' => 'magesetu_semantic_search_vectordb_opensearch_password',
            'timeout' => 'magesetu_semantic_search_vectordb_opensearch_server_timeout'
        ];
    }
}
