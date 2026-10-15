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

namespace MageSetu\SemanticSearch\Model\Indexer\SemanticSearch;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Action\Full as ActionFull;
use MageSetu\SemanticSearch\Model\Indexer\SemanticSearch\Action\ListAction as ActionList;

/**
 * Custom indexer for semantic search product embeddings.
 *
 * Two entry points:
 *  - executeFull()  → triggered by CLI / scheduled reindex / deploy hooks
 *  - execute($ids)  → triggered by Mview after changelog accumulation
 */
class Indexer implements ActionInterface, MviewActionInterface
{
    /**
     * Class constructor
     *
     * @param ActionFull $actionFull
     * @param ActionList $actionList
     */
    public function __construct(
        private readonly ActionFull $actionFull,
        private readonly ActionList $actionList
    ) {
    }

    /**
     * Full reindex — triggered by: bin/magento indexer:reindex magesetu_semanticsearch_product
     */
    public function executeFull(): void
    {
        $this->actionFull->execute();
    }

    /**
     * Partial update — triggered by Mview (Update on Schedule mode).
     * With a deduplicated array of changed entity IDs.
     * Mview guarantees each ID appears at most once.
     *
     * @param int[]|string[] $ids  Entity IDs from the Mview changelog.
     */
    public function execute($ids): void
    {
        $this->actionList->execute($ids);
    }

    /**
     * Called by Magento's indexer framework for a list of IDs
     * (e.g. mass action in admin). Delegates to execute().
     *
     * @param int[] $ids
     */
    public function executeList(array $ids): void
    {
        $this->actionList->execute($ids);
    }

    /**
     * Called by Magento's indexer framework for a single entity.
     *
     * Delegates to execute().
     *
     * @param int $id
     */
    public function executeRow($id): void
    {
        $this->actionList->execute([$id]);
    }
}
