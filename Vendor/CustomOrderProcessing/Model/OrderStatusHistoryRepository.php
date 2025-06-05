<?php
namespace Vendor\CustomOrderProcessing\Model;

class OrderStatusHistoryRepository
{
    /**
     * @var OrderStatusHistoryFactory
     */
    protected $orderStatusHistoryFactory;

    /**
     * @param OrderStatusHistoryFactory $orderStatusHistoryFactory
     */
    public function __construct(
        OrderStatusHistoryFactory $orderStatusHistoryFactory
    ) {
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
    }

    /**
     * Save order status history
     *
     * @param int $orderId
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    public function save($orderId, $oldStatus, $newStatus)
    {
        $history = $this->orderStatusHistoryFactory->create();
        $history->setData([
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
        $history->save();
    }
} 