<?php
namespace Vendor\CustomOrderProcessing\Model;

use Magento\Framework\Model\AbstractModel;

class OrderStatusHistory extends AbstractModel
{
    const CACHE_TAG = 'custom_order_status_history';

    protected $_cacheTag = self::CACHE_TAG;

    protected function _construct()
    {
        $this->_init(\Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        // Use a unique cache tag for each record
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
} 