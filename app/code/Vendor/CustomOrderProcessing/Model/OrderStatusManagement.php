<?php
namespace Vendor\CustomOrderProcessing\Model;

use Vendor\CustomOrderProcessing\Api\OrderStatusManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateRequestInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Vendor\CustomOrderProcessing\Model\OrderStatusHistoryRepository;
use Vendor\CustomOrderProcessing\Helper\ApiResponseHelper;
use Vendor\CustomOrderProcessing\Helper\RateLimiter;
use Vendor\CustomOrderProcessing\Logger\Logger;

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
     * @var ApiResponseHelper
     */
    protected $apiResponseHelper;

    /**
     * @var RateLimiter
     */
    protected $rateLimiter;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCommentSender $orderCommentSender
     * @param OrderStatusHistoryRepository $orderStatusHistoryRepository
     * @param ApiResponseHelper $apiResponseHelper
     * @param RateLimiter $rateLimiter
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderCommentSender $orderCommentSender,
        OrderStatusHistoryRepository $orderStatusHistoryRepository,
        ApiResponseHelper $apiResponseHelper,
        RateLimiter $rateLimiter,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCommentSender = $orderCommentSender;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->apiResponseHelper = $apiResponseHelper;
        $this->rateLimiter = $rateLimiter;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function updateOrderStatus(OrderStatusUpdateRequestInterface $request)
    {
        try {
            // Call Rate Limiter before proceeding
            if ($this->rateLimiter->checkRateLimit()) {
                return $this->apiResponseHelper->error(__('Rate limit exceeded. Please wait before making more requests.'), 210);
            }

            $orderId = $request->getOrderId();
            $status = $request->getStatus();

            if (!$orderId) {
                return $this->apiResponseHelper->error(__("Order ID cannot be empty."), 201);
            }
            if (!is_numeric($orderId) || $orderId <= 0) {
                return $this->apiResponseHelper->error(__("Invalid Order ID format."), 202);
            }
            if (!$status) {
                return $this->apiResponseHelper->error(__("Status cannot be empty."), 203);
            }
            if (!preg_match('/^[a-z_]+$/i', $status)) {
                return $this->apiResponseHelper->error(__("Invalid order status format."), 204);
            }

            $order = $this->orderRepository->get($orderId);
            if (!$order || !$order->getEntityId()) {
                return $this->apiResponseHelper->error(__("Order with ID %1 does not exist.", $orderId), 205);
            }

            $currentStatus = $order->getStatus();
            if ($currentStatus === $status) {
                return $this->apiResponseHelper->error(__("Order status is already set to %1.", $status), 206);
            }

            // Validate allowed status transitions
            $allowedStatuses = $order->getConfig()->getStateStatuses($order->getState());
            if (!array_key_exists($status, $allowedStatuses)) {
                return $this->apiResponseHelper->error(
                    __("Status transition from %1 to %2 is not allowed.", $currentStatus, $status),
                    207
                );
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
                return $this->apiResponseHelper->error(
                    __("Failed to retrieve order status history for order ID %1.", $orderId),
                    208
                );
            }

            return $this->apiResponseHelper->success($history, __("Order status updated successfully to %1.", $status), 200);
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage(), ['order_id' => $request->getOrderId()]);
            return $this->apiResponseHelper->error($e->getMessage(), 209);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['order_id' => $request->getOrderId()]);
            return $this->apiResponseHelper->error(__("An unexpected error occurred: %1", $e->getMessage()), 209);
        }
    }
}