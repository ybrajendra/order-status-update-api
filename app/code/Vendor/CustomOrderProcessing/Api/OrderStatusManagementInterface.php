<?php
namespace Vendor\CustomOrderProcessing\Api;

use Vendor\CustomOrderProcessing\Api\Data\OrderStatusUpdateRequestInterface;

interface OrderStatusManagementInterface
{
    /**
     * Update order status by order ID
     * @param OrderStatusUpdateRequestInterface $request
     * @return \Vendor\CustomOrderProcessing\Api\Data\ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOrderStatus(OrderStatusUpdateRequestInterface $request);
} 