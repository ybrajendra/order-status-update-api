<?php
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateRequestInterface;
use Vendor\CustomOrderProcessing\Model\OrderStatusManagement;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Vendor\CustomOrderProcessing\Model\OrderStatusHistoryRepository;
use Vendor\CustomOrderProcessing\Helper\ApiResponseHelper;
use Vendor\CustomOrderProcessing\Helper\RateLimiter;
use Vendor\CustomOrderProcessing\Logger\Logger;

class OrderStatusManagementTest extends TestCase
{
    /** @var OrderRepositoryInterface&MockObject */
    private $orderRepository;

    /** @var OrderCommentSender&MockObject */
    private $orderCommentSender;

    /** @var OrderStatusHistoryRepository&MockObject */
    private $orderStatusHistoryRepository;

    /** @var ApiResponseHelper&MockObject */
    private $apiResponseHelper;

    /** @var RateLimiter&MockObject */
    private $rateLimiter;

    /** @var Logger&MockObject */
    private $logger;

    /** @var OrderStatusManagement */
    private $sut;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderCommentSender = $this->createMock(OrderCommentSender::class);
        $this->orderStatusHistoryRepository = $this->createMock(OrderStatusHistoryRepository::class);
        $this->apiResponseHelper = $this->createMock(ApiResponseHelper::class);
        $this->rateLimiter = $this->createMock(RateLimiter::class);
        $this->logger = $this->createMock(Logger::class);

        $this->sut = new OrderStatusManagement(
            $this->orderRepository,
            $this->orderCommentSender,
            $this->orderStatusHistoryRepository,
            $this->apiResponseHelper,
            $this->rateLimiter,
            $this->logger
        );
    }

    /**
     * Test successful status update with "shipping" status triggers email.
     */
    public function testUpdateOrderStatusSuccessWithEmail(): void
    {
        $orderId = 3;
        $oldStatus = 'ready_to_ship';
        $newStatus = 'shipped';
        $state = 'complete'; // Assume state is 'complete'

        $request = $this->buildRequestMock($orderId, $newStatus);

        // Mock rate limiter to allow the request
        $this->rateLimiter->method('checkRateLimit')->willReturn(false);

        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($oldStatus);
        $order->method('getEntityId')->willReturn($orderId);
        $order->method('getState')->willReturn($state);

        $orderConfig = $this->createMock(OrderConfig::class);
        $order->method('getConfig')->willReturn($orderConfig);
        $orderConfig->expects($this->once())->method('getStateStatuses')->with($state) //  state is 'complete'
            ->willReturn(['ready_to_ship' => 'Ready to ship', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'complete' => 'Complete']);

        $order->expects($this->once())->method('setStatus')->with($newStatus);

        $historyMock = $this->createMock(\Magento\Sales\Api\Data\OrderStatusHistoryInterface::class);
        $historyMock->expects($this->once())
            ->method('setIsCustomerNotified')
            ->with(true) // Email should be sent for shipping status
            ->willReturnSelf();

        $comment = "Status changed to $newStatus";
        $order->expects($this->once())->method('addStatusHistoryComment')
            ->with($comment, $newStatus)
            ->willReturn($historyMock);

        $this->orderRepository->method('get')->willReturn($order);
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $this->orderCommentSender->expects($this->once())->method('send')->with($order, true, $comment);

        $history = [
            [
                'entity_id' => 1,
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'created_at' => '2024-06-12 12:34:56'
            ]
        ];
        $this->orderStatusHistoryRepository->expects($this->once())
            ->method('getStatusHistoryByOrder')
            ->with($orderId)
            ->willReturn($history);

        // Mock the API response
        $response = $this->createMock(\Vendor\CustomOrderProcessing\Model\Data\Response::class);
        $response->method('getSuccess')->willReturn(true);
        $response->method('getCode')->willReturn(200);
        $response->method('getMessage')->willReturn("Status changed to $newStatus");
        $response->method('getResult')->willReturn($history);
        $this->apiResponseHelper->method('success')
            ->willReturn($response);

        $this->sut->updateOrderStatus($request);
    }

    /**
     * Test successful status update with non-shipping status does not send email.
     */
    public function testUpdateOrderStatusWithoutEmail(): void
    {
        $orderId = 2;
        $oldStatus = 'processing';
        $newStatus = 'preparing_order';
        $state = 'processing'; // Assume state is 'processing'

        $request = $this->buildRequestMock($orderId, $newStatus);

        // Mock rate limiter to allow the request
        $this->rateLimiter->method('checkRateLimit')->willReturn(false);

        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($oldStatus);
        $order->method('getEntityId')->willReturn($orderId);
        $order->method('getState')->willReturn($state);

        $orderConfig = $this->createMock(OrderConfig::class);
        $order->method('getConfig')->willReturn($orderConfig);
        $orderConfig->expects($this->once())->method('getStateStatuses')->with($state) //  state is 'processing'
            ->willReturn(['processing' => 'Processing', 'preparing_order' => 'Preparing Order']);

        $order->expects($this->once())->method('setStatus')->with($newStatus);

        $historyMock = $this->createMock(\Magento\Sales\Api\Data\OrderStatusHistoryInterface::class);
        $historyMock->expects($this->once())
            ->method('setIsCustomerNotified')
            ->with(false)
            ->willReturnSelf();
        $order->expects($this->once())->method('addStatusHistoryComment')
            ->with("Status changed to $newStatus", $newStatus)
            ->willReturn($historyMock);

        $this->orderRepository->method('get')->willReturn($order);
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $this->orderCommentSender->expects($this->never())->method('send');

        $history = [
                [
                    'entity_id' => 42,
                    'order_id' => 2,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'created_at' => '2024-06-12 12:34:56'
                ]
            ];
        $this->orderStatusHistoryRepository->expects($this->once())
            ->method('getStatusHistoryByOrder')
            ->with($orderId)
            ->willReturn($history);

        // Mock the API response
        $response = $this->createMock(\Vendor\CustomOrderProcessing\Model\Data\Response::class);
        $response->method('getSuccess')->willReturn(true);
        $response->method('getCode')->willReturn(200);
        $response->method('getMessage')->willReturn("Status changed to $newStatus");
        $response->method('getResult')->willReturn($history);
        $this->apiResponseHelper->method('success')
            ->willReturn($response);

        $this->sut->updateOrderStatus($request);
    }

    /**
     * Test no status update or email is triggered if new status matches current.
     */
    public function testUpdateOrderStatusNoChange(): void
    {
        $orderId = 2;
        $currentStatus = 'processing';
        $state = 'processing';

        $request = $this->buildRequestMock($orderId, $currentStatus);

        // Mock rate limiter to allow the request
        $this->rateLimiter->method('checkRateLimit')->willReturn(false);

        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($currentStatus);
        $order->method('getEntityId')->willReturn($orderId);
        $order->method('getState')->willReturn($state);

        $orderConfig = $this->createMock(OrderConfig::class);
        $order->method('getConfig')->willReturn($orderConfig);
        $orderConfig->expects($this->never())->method('getStateStatuses')->with($state)
            ->willReturn(['processing' => 'Processing', 'preparing_order' => 'Preparing Order']);

        $this->orderRepository->method('get')->willReturn($order);

        // $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        // $this->expectExceptionMessage('Order status is already set to preparing_order.');

        // Mock the API response
        $response = $this->createMock(\Vendor\CustomOrderProcessing\Model\Data\Response::class);
        $response->method('getSuccess')->willReturn(false);
        $response->method('getCode')->willReturn(206);
        $response->method('getMessage')->willReturn("Order status is already set to preparing order.");
        $response->method('getResult')->willReturn([]);
        $this->apiResponseHelper->method('error')
            ->willReturn($response);

        $this->sut->updateOrderStatus($request);
    }

    /**
     * Test order not found scenario throws exception.
     */
    public function testUpdateOrderStatusInvalidOrder(): void
    {
        $orderId = 1;
        $newStatus = 'shipped';

        $request = $this->buildRequestMock($orderId, $newStatus);

        $this->orderRepository->method('get')
            ->with($orderId)
            ->willThrowException(new LocalizedException(__('Order not found')));

        // $this->expectException(LocalizedException::class);

        // Mock the API response
        $response = $this->createMock(\Vendor\CustomOrderProcessing\Model\Data\Response::class);
        $response->method('getSuccess')->willReturn(false);
        $response->method('getCode')->willReturn(205);
        $response->method('getMessage')->willReturn("Order with ID $orderId does not exist.");
        $response->method('getResult')->willReturn([]);
        $this->apiResponseHelper->method('error')
            ->willReturn($response);

        $this->sut->updateOrderStatus($request);
    }

    /**
     * Helper to build a request mock.
     */
    private function buildRequestMock(int $orderId, string $status): OrderStatusUpdateRequestInterface
    {
        $request = $this->createMock(OrderStatusUpdateRequestInterface::class);
        $request->method('getOrderId')->willReturn($orderId);
        $request->method('getStatus')->willReturn($status);
        return $request;
    }
}
