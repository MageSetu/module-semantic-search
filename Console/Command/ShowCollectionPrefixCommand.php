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
use MageSetu\SemanticSearch\Api\EnvironmentResolverInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ShowCollectionPrefixCommand class
 * CLI Command: show the currently configured vector database collection prefix from environment
 *
 * Usage:
 *   php bin/magento magesetu:semantic-search:show-collection-prefix
 *
 * @api
 * @since 1.0.0
 */
class ShowCollectionPrefixCommand extends Command
{
    private const COMMAND_NAME = 'magesetu:semantic-search:show-collection-prefix';
    private const COMMAND_DESCRIPTION =
        'Show the currently configured vector database collection prefix from environment.';

    /**
     * Class constructor
     *
     * @param EnvironmentResolverInterface $environmentResolver
     * @param string|null $name = null
     */
    public function __construct(
        private readonly EnvironmentResolverInterface $environmentResolver,
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
            ->setDescription(self::COMMAND_DESCRIPTION);

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $prefix = $this->environmentResolver->getVectorStorageCollectionPrefix();

        if ($prefix === '') {
            $output->writeln('<comment>No environment prefix is set for semantic search vector database.</comment>');
            $commandToSet = 'bin/magento setup:config:set --semantic-search-collection-prefix="staging"';
            $output->writeln('Example to set:' . $commandToSet);
            return Command::FAILURE;
        }

        $output->writeln('<info>Semantic search vector database environment prefix:</info> ' . $prefix);
        return Command::SUCCESS;
    }
}
