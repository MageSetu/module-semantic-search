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

namespace MageSetu\SemanticSearch\Controller\Adminhtml\Products;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use MageSetu\SemanticSearch\Model\ResourceModel\Product\CollectionFactory;
use MageSetu\SemanticSearch\Model\ResourceModel\Product\Collection;
use MageSetu\SemanticSearch\Api\Data\ProductInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class to handle mass embedding status update
 */
class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageSetu_SemanticSearch::manage_products';

    /**
     * Class constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute post request
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $status = (int) $this->getRequest()->getParam('status');

            /** @var Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();

            $collection->updateColumnValue(ProductInterface::EMBEDDING_STATUS, $status);

            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been updated.', $collectionSize)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/grid');
    }
}
