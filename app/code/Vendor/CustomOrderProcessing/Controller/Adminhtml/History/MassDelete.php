<?php
namespace Vendor\CustomOrderProcessing\Controller\Adminhtml\History;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Vendor\CustomOrderProcessing\Model\OrderStatusHistoryRepository;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Vendor_CustomOrderProcessing::history';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var OrderStatusHistoryRepository
     */
    protected $orderStatusHistoryRepository;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        OrderStatusHistoryRepository $orderStatusHistoryRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;
        foreach ($collection as $item) {
            $item->delete();
            $this->orderStatusHistoryRepository->invalidateCache($item->getOrderId());
            $deleted++;
        }

        $this->messageManager->addSuccessMessage(__('%1 record(s) have been deleted.', $deleted));
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}