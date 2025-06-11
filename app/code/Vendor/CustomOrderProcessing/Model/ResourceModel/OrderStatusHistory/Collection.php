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

    protected function _renderFiltersBefore()
    {
        // Join sales_order for order_number (increment_id)
        $this->getSelect()->joinLeft(
            ['so' => $this->getTable('sales_order')],
            'main_table.order_id = so.entity_id',
            ['order_number' => 'increment_id']
        );
        parent::_renderFiltersBefore();
    }

    public function addFieldToFilter($field, $condition = null)
    {
        // Intercept "order_number" and map to the joined field
        if ($field == 'order_number') {
            $field = 'so.increment_id';
        }

        // Intercept "entity_id" to ensure it refers to the main table
        // This is necessary to avoid ambiguity in the query
        if ($field == 'entity_id') {
            $field = 'main_table.entity_id';
        }
        return parent::addFieldToFilter($field, $condition);
    }
} 