<?php
namespace Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Vendor\CustomOrderProcessing\Model\OrderStatusHistory::class,
            \Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory::class
        );
    }
} 