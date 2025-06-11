<?php
namespace Vendor\CustomOrderProcessing\Controller\Adminhtml\History;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Vendor_CustomOrderProcessing::history';

    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Vendor_CustomOrderProcessing::history');
        $resultPage->getConfig()->getTitle()->prepend(__('Order Status Change History'));
        return $resultPage;
    }
}