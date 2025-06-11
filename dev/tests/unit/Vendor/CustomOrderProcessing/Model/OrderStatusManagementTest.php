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

class OrderStatusManagementTest extends TestCase
{
    /** @var OrderRepositoryInterface&MockObject */
    private $orderRepository;

    /** @var OrderCommentSender&MockObject */
    private $orderCommentSender;

    /** @var OrderStatusHistoryRepository&MockObject */
    private $orderStatusHistoryRepository;

    /** @var OrderStatusManagement */
    private $sut;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderCommentSender = $this->createMock(OrderCommentSender::class);
        $this->orderStatusHistoryRepository = $this->createMock(OrderStatusHistoryRepository::class);

        $this->sut = new OrderStatusManagement(
            $this->orderRepository,
            $this->orderCommentSender,
            $this->orderStatusHistoryRepository
        );
    }

    /**
     * Test successful status update with "shipping" status triggers email.
     */
    public function testUpdateOrderStatusSuccessWithEmail(): void
    {
        $orderId = 2;
        $oldStatus = 'ready_to_ship';
        $newStatus = 'shipped';
        $state = 'complete'; // Assume state is 'complete'

        $request = $this->buildRequestMock($orderId, $newStatus);

        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($oldStatus);
        $order->method('getEntityId')->willReturn($orderId);
        $order->method('getState')->willReturn($state);

        $orderConfig = $this->createMock(OrderConfig::class);
        $order->method('getConfig')->willReturn($orderConfig);
        $orderConfig->expects($this->once())->method('getStateStatuses')->with($state) //  state is 'complete'
            ->willReturn(['ready_to_ship' => 'Ready to ship', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'completed' => 'Completed']);

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

        $this->orderStatusHistoryRepository->expects($this->once())
            ->method('getStatusHistoryByOrder')
            ->with($orderId)
            ->willReturn([
                [
                    'entity_id' => 42,
                    'order_id' => 2,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'created_at' => '2024-06-12 12:34:56'
                ]
            ]);

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

        $this->orderStatusHistoryRepository->expects($this->once())
            ->method('getStatusHistoryByOrder')
            ->with($orderId)
            ->willReturn([
                [
                    'entity_id' => 42,
                    'order_id' => 2,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'created_at' => '2024-06-12 12:34:56'
                ]
            ]);

        $this->sut->updateOrderStatus($request);
    }

    /**
     * Test no status update or email is triggered if new status matches current.
     */
    public function testUpdateOrderStatusNoChange(): void
    {
        $orderId = 2;
        $currentStatus = 'preparing_order';
        $state = 'processing';

        $request = $this->buildRequestMock($orderId, $currentStatus);

        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($currentStatus);
        $order->method('getEntityId')->willReturn($orderId);
        $order->method('getState')->willReturn($state);

        $orderConfig = $this->createMock(OrderConfig::class);
        $order->method('getConfig')->willReturn($orderConfig);
        $orderConfig->expects($this->never())->method('getStateStatuses')->with($state)
            ->willReturn(['processing' => 'Processing', 'preparing_order' => 'Preparing Order']);

        $this->orderRepository->method('get')->willReturn($order);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Order status is already set to preparing_order.');

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

        $this->expectException(LocalizedException::class);

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
