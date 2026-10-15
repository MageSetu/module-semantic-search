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

namespace MageSetu\SemanticSearch\Api;

/**
 * Semantic search product management interface
 *
 * @api
 * @since 1.0.0
 */
interface ProductManagementInterface
{
    /**
     * Insert or update multiple records in bulk.
     *
     * @param array $rawData
     * @param int $storeId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveBulkPendingPayloads(array $rawData, int $storeId): void;

    /**
     * Mark records as succeeded in the database table
     *
     * @param array $rawData - Array of store_id and product_id
     * @param int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function markSucceeded(array $rawData, ?int $storeId = null): void;

    /**
     * Mark records as failed in the database table
     *
     * @param array $rawData - Array of store_id and product_id
     * @param string $message failure message
     * @param int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function markFailed(array $rawData, string $message, ?int $storeId = null): void;
}
