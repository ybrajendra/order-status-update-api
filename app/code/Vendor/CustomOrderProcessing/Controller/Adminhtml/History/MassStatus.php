<?php
namespace Vendor\CustomOrderProcessing\Controller\Adminhtml\History;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;

class MassStatus extends Action
{
    const ADMIN_RESOURCE = 'Vendor_CustomOrderProcessing::history';

    protected $filter;
    protected $collectionFactory;
    protected $request;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $status = $this->getRequest()->getParam('status');
        // Created just to add mass action controller action
        
        $this->messageManager->addSuccessMessage(__('Status has been updated.'));
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}