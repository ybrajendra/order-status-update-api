<?php
namespace Vendor\CustomOrderProcessing\Model;

use Magento\Framework\App\CacheInterface;

class OrderStatusHistoryRepository
{
    const CACHE_TAG = 'order_status_history';

    protected $cache;

    /**
     * @var OrderStatusHistoryFactory
     */
    protected $orderStatusHistoryFactory;

    /**
     * @param OrderStatusHistoryFactory $orderStatusHistoryFactory
     */
    public function __construct(
        OrderStatusHistoryFactory $orderStatusHistoryFactory,
        CacheInterface $cache
    ) {
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->cache = $cache;
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

        // Invalidate cache for this order
        $this->invalidateCache($orderId);
    }

    public function getStatusHistoryByOrder($orderId)
    {
        $cacheKey = self::CACHE_TAG . '_' . $orderId;
        $data = $this->cache->load($cacheKey);
        if ($data !== false) {
            return unserialize($data);
        }

        // fetch from DB...
        $collection = $this->orderStatusHistoryFactory->create()->getCollection()
            ->addFieldToFilter('order_id', $orderId);
        $history = $collection->getData();

        $this->cache->save(serialize($history), $cacheKey, [self::CACHE_TAG]);
        return $history;
    }

    public function invalidateCache($orderId)
    {
        $cacheKey = self::CACHE_TAG . '_' . $orderId;
        $this->cache->remove($cacheKey);
    }
}