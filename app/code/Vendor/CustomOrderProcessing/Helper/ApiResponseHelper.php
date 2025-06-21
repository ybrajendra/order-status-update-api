<?php
namespace Vendor\CustomOrderProcessing\Helper;

use \Magento\Framework\Webapi\Rest\Response;
use Vendor\CustomOrderProcessing\Model\Data\ResponseFactory;

class ApiResponseHelper
{
    /**
     * @var Response
     */
    protected $responseFactory;

    /**
     * ApiResponseHelper constructor.
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        ResponseFactory $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Generate a successful API response
     *  @param mixed $data
     *  @param string $message
     *  @return Response
     */
    public function success($data = [], string $message = 'Success', $code = null)
    {
        $response = $this->responseFactory->create();
        $response->setSuccess(true);
        $response->setCode($code ?? 200);
        $response->setMessage($message);
        $response->setResult($data);

        return $response;
    }

    /**
     * Generate an error API response
     *  @param string $message
     *  @param int $code
     *  @param mixed $data
     *  @return Response
     */
    public function error(string $message = 'An error occurred', $code = 400, $data = [])
    {
        $response = $this->responseFactory->create();
        $response->setSuccess(false);
        $response->setCode($code);
        $response->setMessage($message);
        $response->setResult($data);

        return $response;
    }
}
