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

namespace MageSetu\SemanticSearch\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Forward;
use MageSetu\SemanticSearch\Api\ConfigInterface;

/**
 * Semantic search page renderer class
 * @api
 * @since 1.0.0
 */
class Index implements HttpGetActionInterface
{
    /**
     * Class constructor
     *
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $forwardFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * Render page
     *
     * @return Page
     */
    public function execute()
    {
        if (!$this->config->isModuleEnabled()) {
            /** @var Forward $forward */
            $forward = $this->forwardFactory->create();
            return $forward->forward('defaultNoRoute');
        }

        return $this->resultPageFactory->create();
    }
}
