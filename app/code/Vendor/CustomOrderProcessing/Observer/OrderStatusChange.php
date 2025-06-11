<?php
namespace Vendor\CustomOrderProcessing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Vendor\CustomOrderProcessing\Model\OrderStatusHistoryRepository;

class OrderStatusChange implements ObserverInterface
{
    /**
     * @var OrderStatusHistoryRepository
     */
    protected $orderStatusHistoryRepository;

    /**
     * @param OrderStatusHistoryRepository $orderStatusHistoryRepository
     */
    public function __construct(
        OrderStatusHistoryRepository $orderStatusHistoryRepository
    ) {
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        
        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getStatus();

        // Only insert if status has changed
        if ($oldStatus !== $newStatus) {
            $this->orderStatusHistoryRepository->save(
                $order->getId(),
                $oldStatus,
                $newStatus
            );
        }
    }
} 