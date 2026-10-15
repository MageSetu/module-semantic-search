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

namespace MageSetu\SemanticSearch\Model\Vector\Storage;

use MageSetu\SemanticSearch\Api\VectorStorageInterface;
use MageSetu\SemanticSearch\Api\EnvironmentResolverInterface;
use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterfaceFactory;
use MageSetu\SemanticSearch\Logger\Logger;

/**
 * Abstract class for some common methods
 */
abstract class AbstractStorage implements VectorStorageInterface
{
    /**
     * Base name for the collection/index.
     */
    protected const COLLECTION_BASE_NAME = 'magesetu_semantic_search_products';

    protected const MAX_BULK_BYTES = 5 * 1024 * 1024; // ~5MB
    protected const MAX_BULK_DOCS = 100;             // Max bulk processing cap
    protected const MAX_BULK_DELETE_DOCS = 1000;     // Max bulk delete cap

    /**
     * Class constructor
     *
     * @param EnvironmentResolverInterface $environmentResolver
     * @param VectorDocumentInterfaceFactory $documentFactory Factory for VectorDocumentInterface instances.
     * @param Logger $logger
     */
    public function __construct(
        protected readonly EnvironmentResolverInterface $environmentResolver,
        protected readonly VectorDocumentInterfaceFactory $documentFactory,
        protected readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCollectionName(int $storeId): string
    {
        $prefix = $this->environmentResolver->getVectorStorageCollectionPrefix();

        if ($prefix) {
            return sprintf("%s_%s_%d", $prefix, self::COLLECTION_BASE_NAME, $storeId);
        } else {
            return sprintf("%s_%d", self::COLLECTION_BASE_NAME, $storeId);
        }
    }
}
