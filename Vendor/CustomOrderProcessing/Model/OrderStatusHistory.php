<?php
namespace Vendor\CustomOrderProcessing\Model;

use Magento\Framework\Model\AbstractModel;

class OrderStatusHistory extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory::class);
    }
} 