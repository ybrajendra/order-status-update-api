<?php
namespace Vendor\CustomOrderProcessing\Model\Data;

use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateRequestInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class OrderStatusUpdateRequest extends AbstractSimpleObject implements OrderStatusUpdateRequestInterface
{
    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->_get('order_id');
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData('order_id', $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->_get('status');
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }
} 