<?php
namespace Vendor\CustomOrderProcessing\Model;

use Vendor\CustomOrderProcessing\Api\OrderStatusManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateRequestInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;

class OrderStatusManagement implements OrderStatusManagementInterface
{
    const SHIPPING_STATUS = 'shipped';

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderCommentSender
     */
    protected $orderCommentSender;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCommentSender $orderCommentSender
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderCommentSender $orderCommentSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCommentSender = $orderCommentSender;
    }

    /**
     * {@inheritdoc}
     */
    public function updateOrderStatus(OrderStatusUpdateRequestInterface $request)
    {
        $orderId = $request->getOrderId();
        $status = $request->getStatus();
        $order = $this->orderRepository->get($orderId);
        if (!$order || !$order->getEntityId()) {
            throw new LocalizedException(__("Order with ID %1 does not exist.", $orderId));
        }

        // Validate allowed status transitions
        $currentStatus = $order->getStatus();
        $allowedStatuses = $order->getConfig()->getStateStatuses($order->getState());
        if (!array_key_exists($status, $allowedStatuses)) {
            throw new LocalizedException(__("Status transition from %1 to %2 is not allowed.", $currentStatus, $status));
        }

        $order->setStatus($status);

        $notify = false;
        // Notify customer when order is shipped, this will use the default order update email template
        if ($status === self::SHIPPING_STATUS) {
            $notify = true;
        }

        $comment = "Status changed to $status";
        $order->addStatusHistoryComment($comment, $status)->setIsCustomerNotified($notify);

        $this->orderRepository->save($order);
        if ($notify) {
            $this->orderCommentSender->send($order, true, $comment); // Sends email to customer
        }
        return true;
    }
} 