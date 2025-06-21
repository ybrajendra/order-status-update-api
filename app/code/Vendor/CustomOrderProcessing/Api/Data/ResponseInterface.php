<?php
namespace Vendor\CustomOrderProcessing\Api\Data;

interface ResponseInterface
{
    /**
     * Get success status of the response
     * @return $this
     */
    public function getSuccess();

    /**
     * Set success status of the response
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * Get message associated with the response
     * @return string
     */
    public function getMessage();

    /**
     * Set message associated with the response
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get response code
     * @return int
     */
    public function getCode();

    /**
     * Set response code
     * @param int $code
     * @return $this
     */
    public function setCode($code);

    /**
     * Get result data
     * @return mixed
     */
    public function getResult();

    /**
     * Set result data
     * @param mixed $data
     * @return $this
     */
    public function setResult($data);
}
