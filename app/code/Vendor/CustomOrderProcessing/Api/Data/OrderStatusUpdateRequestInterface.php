<?php
namespace Vendor\CustomOrderProcessing\Api\Data;

interface OrderStatusUpdateRequestInterface
{
    /**
     * Get order Order ID
     * @return string
     */
    public function getOrderId();

    /**
     * Set order Order ID
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get new status
     * @return string
     */
    public function getStatus();

    /**
     * Set new status
     * @param string $status
     * @return $this
     */
    public function setStatus($status);
} 