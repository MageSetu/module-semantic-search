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

namespace MageSetu\SemanticSearch\Console\Command;

use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use MageSetu\SemanticSearch\Model\Service\OrphanedEmbeddingCleaner;
use MageSetu\SemanticSearch\Logger\Logger;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * RemoveOrphanedEmbeddingsCommand class
 * CLI Command: remove orphaned vectors from the vector database
 *
 * Usage:
 *   php bin/magento magesetu:semantic-search:remove-orphaned
 *
 * @api
 * @since 1.0.0
 */
class RemoveOrphanedEmbeddingsCommand extends Command
{
    private const COMMAND_NAME = 'magesetu:semantic-search:remove-orphaned';
    private const COMMAND_DESCRIPTION =
        'Remove orphaned items from the vector database '
        . 'that no longer correspond to valid Magento products(all stores).';

    private const OPTION_STORE_ID = 'store-id';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private const DEFAULT_BATCH_SIZE = '1000';

    /**
     * Class constructor
     *
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param OrphanedEmbeddingCleaner $cleaner
     * @param Logger $logger
     * @param string|null $name = null
     */
    public function __construct(
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly OrphanedEmbeddingCleaner $cleaner,
        private readonly Logger $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_STORE_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'Store ID to process (default: all stores)'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Report orphans without deleting them'
            )
            ->addOption(
                self::OPTION_BATCH_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Batch size for scroll & bulk delete',
                self::DEFAULT_BATCH_SIZE
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

            $dryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);
            $batchSize = (int) $input->getOption(self::OPTION_BATCH_SIZE);
            $storeIdOption = $input->getOption(self::OPTION_STORE_ID);

            $storeIds = $storeIdOption !== null
                ? [$storeIdOption]
                : array_keys($this->storeManager->getStores());

            foreach ($storeIds as $storeId) {
                $storeId = (int) $storeId;
                $this->printCli($output, 'info', sprintf("Processing for store id: %d", $storeId));

                $count = $this->cleaner->cleanForStore($storeId, $batchSize, $dryRun);

                $this->printCli($output, 'info', sprintf(
                    "Removed %d documents for store id: %d",
                    $count,
                    $storeId
                ));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error("Error while running command.", [
                'command' => self::COMMAND_NAME,
                'error' => $e->getMessage()
            ]);

            $this->printCli($output, 'error', $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->logger->error("Error while running command.", [
                'command' => self::COMMAND_NAME,
                'exception' => $e
            ]);

            $this->printCli($output, 'error', $e->getMessage() . " Check logs for more details.");
            return Command::FAILURE;
        }

        $this->printCli($output, 'info', "Orphaned embeddings removed successfully.");
        return Command::SUCCESS;
    }

    /**
     * Show message to CLI in format
     *
     * @param OutputInterface $output
     * @param string $type
     * @param string $message
     * @return void
     */
    private function printCli(OutputInterface $output, string $type, string $message)
    {
        $type = in_array($type, ['info', 'comment', 'error', 'question']) ? $type : 'comment';

        $output->writeln(sprintf("<%s>%s</%s>", $type, $message, $type));
    }
}
