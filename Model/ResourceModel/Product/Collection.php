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

namespace MageSetu\SemanticSearch\Model\ResourceModel\Product;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageSetu\SemanticSearch\Model\ResourceModel\Product as ProductResource;
use MageSetu\SemanticSearch\Model\Product;

/**
 * Semantic Search Product Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Resource collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Product::class,
            ProductResource::class
        );
    }

    /**
     * Bulk update a single column for all IDs currently loaded in this collection, in chunks.
     *
     * @param string $field
     * @param mixed $value
     * @param int $chunkSize
     * @return int Number of affected rows
     * @throws \Exception
     */
    public function updateColumnValue(string $field, $value, int $chunkSize = 1000): int
    {
        $ids = $this->getAllIds();

        if (empty($ids)) {
            return 0;
        }

        $connection = $this->getResource()->getConnection();
        $tableName = $this->getResource()->getMainTable();
        $idField = $this->getResource()->getIdFieldName();

        $connection->beginTransaction();
        try {
            $affected = 0;
            foreach (array_chunk($ids, $chunkSize) as $chunk) {
                $affected += $connection->update(
                    $tableName,
                    [$field => $value],
                    [$idField . ' IN (?)' => $chunk]
                );
            }
            $connection->commit();

            return $affected;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Bulk delete all rows currently loaded in this collection.
     *
     * @param int $chunkSize
     * @return int Number of deleted rows
     * @throws \Exception
     */
    public function deleteItems(int $chunkSize = 1000): int
    {
        $ids = $this->getAllIds();
        if (empty($ids)) {
            return 0;
        }

        $connection = $this->getResource()->getConnection();
        $tableName = $this->getResource()->getMainTable();
        $idField = $this->getResource()->getIdFieldName();

        $connection->beginTransaction();
        try {
            $deleted = 0;
            foreach (array_chunk($ids, $chunkSize) as $chunk) {
                $deleted += $connection->delete($tableName, [$idField . ' IN (?)' => $chunk]);
            }
            $connection->commit();

            return $deleted;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
