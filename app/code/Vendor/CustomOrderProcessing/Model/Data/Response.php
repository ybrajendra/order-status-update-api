<?php

namespace Vendor\CustomOrderProcessing\Model\Data;

use Vendor\CustomOrderProcessing\Api\Data\ResponseInterface;
use Magento\Framework\DataObject;

class Response extends DataObject implements ResponseInterface
{
    /**
     * @inheritdoc
     */
    public function getSuccess()
    {
        return $this->getData('success');
    }

    /**
     * @inheritdoc
     */
    public function setSuccess($val)
    {
        return $this->setData('success', $val);
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->getData('message');
    }

    /**
     * @inheritdoc
     */
    public function setMessage($val)
    {
        return $this->setData('message', $val);
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->getData('code');
    }

    /**
     * @inheritdoc
     */
    public function setCode($val)
    {
        return $this->setData('code', $val);
    }

    /**
     * @inheritdoc
     */
    public function getResult()
    {
        return $this->getData('data');
    }

    /**
     * @inheritdoc
     */
    public function setResult($val)
    {
        return $this->setData('data', $val);
    }
}
