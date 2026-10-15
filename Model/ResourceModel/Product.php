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

namespace MageSetu\SemanticSearch\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MageSetu\SemanticSearch\Api\Data\ProductInterface;

/**
 * Semantic Search Product Resource Model
 */
class Product extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'magesetu_semantic_search_product',
            ProductInterface::ID
        );
    }

    /**
     * Retrieve all product IDs for the specified store.
     *
     * @param int $storeId
     * @return int[]
     */
    public function getProductIdsByStoreId(int $storeId): array
    {
        $connection = $this->getConnection();
        $tableName = $this->getMainTable();

        $select = $connection->select()
            ->from($tableName, ['product_id'])
            ->where('store_id = ?', $storeId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Delete entries matching store ids.
     *
     * Can be used when embedding model provider or model is changed.
     *
     * @param int $storeId
     * @return int
     */
    public function deleteAllProductsFromStoreId(int $storeId): int
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['store_id = ?' => $storeId]
        );
    }

    /**
     * Persist bulk insert data to DB using insertOnDuplicate
     *
     * @param array $bulkInsertData
     * @param array $updateFields
     * @return int - number of the rows affected
     */
    public function persistBulkInsert(array $bulkInsertData, array $updateFields): int
    {
        /*
         * Because product_id and store_id are a UNIQUE KEY, insertOnDuplicate uses
         * MySQL's powerful "ON DUPLICATE KEY UPDATE" feature.
         * It will automatically INSERT the row if it's new, or UPDATE the content_hash
         * and embedding_status if it already exists!
         * Only update these fields if a duplicate key is found
         */
        return $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            $bulkInsertData,
            $updateFields
        );
    }

    /**
     * Pre-loads an array of existing hashes for a batch of product IDs.
     * (Highly recommended for avoiding N+1 query performance issues).
     *
     * @param array $productIds
     * @param int $storeId
     * @return array Returns format: [product_id => content_hash]
     */
    public function getExistingHashesForBatch(array $productIds, int $storeId): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->getConnection();
        $tableName = $this->getMainTable();

        // Fetch all hashes for this chunk of products in ONE query
        $select = $connection->select()
            ->from($tableName, [
                ProductInterface::PRODUCT_ID,
                ProductInterface::CONTENT_HASH,
                ProductInterface::EMBEDDING_STATUS
            ])
            ->where('product_id IN (?)', $productIds)
            ->where('store_id = ?', (int) $storeId);

        // Returns an associative array like:
        // [ 123 => ['content_hash' => 'abc123hash...', 'embedding_status' => 'pending'], ... ]
        $rows = $connection->fetchAll($select);
        $result = [];

        foreach ($rows as $row) {
            $result[$row[ProductInterface::PRODUCT_ID]] = [
                ProductInterface::CONTENT_HASH => $row[ProductInterface::CONTENT_HASH],
                ProductInterface::EMBEDDING_STATUS => $row[ProductInterface::EMBEDDING_STATUS]
            ];
        }

        return $result;
    }
}
