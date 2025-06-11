<?php
declare(strict_types=1);

namespace Vendor\CustomOrderProcessing\Test\Integration\Observer;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\ResourceConnection;

class OrderStatusHistoryTest extends TestCase
{
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var OrderFactory */
    private $orderFactory;

    /** @var ResourceConnection */
    private $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->orderFactory = $objectManager->get(OrderFactory::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testStatusChangeTriggersLogEntry()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $oldStatus = $order->getStatus();
        $orderId = $order->getEntityId();

        // 2. Change status and save again (should trigger observer)
        $order->setStatus('fraud'); // Change to a different status
        $this->orderRepository->save($order);

        // 3. Check custom log table for the entry
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from('sales_order_status_change_history') // <- replace with your actual table name
            ->where('order_id = ?', $orderId)
            ->where('old_status = ?', $oldStatus)
            ->where('new_status = ?', 'fraud');
        $result = $connection->fetchRow($select);

        $this->assertNotEmpty($result, 'Status change log not found in custom table');
        $this->assertEquals($orderId, $result['order_id']);
        $this->assertEquals($oldStatus, $result['old_status']);
        $this->assertEquals('fraud', $result['new_status']);
    }
}
