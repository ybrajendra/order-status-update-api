<?php
namespace Vendor\CustomOrderProcessing\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class OrderStatusOptions implements OptionSourceInterface
{
    protected $statusCollectionFactory;

    public function __construct(CollectionFactory $statusCollectionFactory)
    {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        $collection = $this->statusCollectionFactory->create();
        foreach ($collection as $status) {
            $options[] = [
                'value' => $status->getStatus(),
                'label' => $status->getLabel()
            ];
        }
        return $options;
    }
}