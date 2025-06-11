<?php
namespace Vendor\CustomOrderProcessing\Model;

use Vendor\CustomOrderProcessing\Api\OrderStatusManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateRequestInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Vendor\CustomOrderProcessing\Model\OrderStatusHistoryRepository;

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
     * @var OrderStatusHistoryRepository
     */
    protected $orderStatusHistoryRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCommentSender $orderCommentSender
     * @param OrderStatusHistoryRepository $orderStatusHistoryRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderCommentSender $orderCommentSender,
        OrderStatusHistoryRepository $orderStatusHistoryRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCommentSender = $orderCommentSender;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
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

        $currentStatus = $order->getStatus();
        if ($currentStatus === $status) {
            throw new LocalizedException(__("Order status is already set to %1.", $status));
        }
        if (!$status) {
            throw new LocalizedException(__("Status cannot be empty."));
        }
        if (!is_string($status)) {
            throw new LocalizedException(__("Status must be a string."));
        }

        // Validate allowed status transitions
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

        // return json response of order status history from the repository
        $history = $this->orderStatusHistoryRepository->getStatusHistoryByOrder($orderId);
        if (!$history) {
            throw new LocalizedException(__("Failed to retrieve order status history for order ID %1.", $orderId));
        }

        return $history;
    }
} 