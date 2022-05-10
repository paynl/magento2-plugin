<?php

namespace Paynl\Payment\Plugin\Webapi\RestResponse;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response\Renderer\Json;
use Psr\Log\LoggerInterface;

class JsonPlugin
{

    /** @var Request */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * JsonPlugin constructor.
     * @param Request $request
     */
    public function __construct(Request $request, LoggerInterface $logger)
    {
        $this->request = $request;
        $this->_logger = $logger;
    }

    /**
     * @param Json $jsonRenderer
     * @param callable $proceed
     * @param $data
     * @return mixed
     */
    public function aroundRender(Json $jsonRenderer, callable $proceed, $data)
    {
        $jsonPaths[] = '/V1/paynl/process-encrypted-transaction'; //deze kan denk ik weg, vervangen door diehieronder (cse)
        $jsonPaths[] = '/V1/paynl/cse';
        $jsonPaths[] = '/V1/paynl/cse/status';
        $jsonPaths[] = '/V1/paynl/cse/authorization';
        $jsonPaths[] = '/V1/paynl/cse/authentication';

        $curPath = $this->request->getPathInfo();

        if (in_array($this->request->getPathInfo(), $jsonPaths) && $this->isJson($data)) {
            #$this->_logger->debug('PAY.: aroundRender (json)  : ' . $curPath);
            return $data;
        }

        #       $this->_logger->debug('PAY.: aroundRender (no-json)  : ' . $curPath);

        return $proceed($data);
    }

    /**
     * @param $data
     * @return bool
     */
    private function isJson($data)
    {
        if (!is_string($data)) {
            return false;
        }
        json_decode($data);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}
