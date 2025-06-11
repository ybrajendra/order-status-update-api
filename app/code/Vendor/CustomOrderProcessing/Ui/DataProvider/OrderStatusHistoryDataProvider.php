<?php
namespace Vendor\CustomOrderProcessing\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Vendor\CustomOrderProcessing\Model\ResourceModel\OrderStatusHistory\CollectionFactory;

class OrderStatusHistoryDataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        return [
            'items' => $this->getCollection()->toArray()['items'],
            'totalRecords' => $this->getCollection()->getSize()];
    }
}