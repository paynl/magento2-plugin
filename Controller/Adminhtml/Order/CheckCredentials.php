<?php

namespace Paynl\Payment\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Paynl\Payment\Model\Config;
use \Paynl\Paymentmethods;

class CheckCredentials extends Action
{
    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    protected $config;

    /**
     *
     * @var Magento\Framework\App\RequestInterface;
     */
    protected $scopeConfig;

    /**
     *
     * @var Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     *
     * @var Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;

    public function __construct(
        Context $context,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        JsonFactory $jsonFactory
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $error = null;
        $status = 1;

        $tokencode = $this->request->getParam('tokencode');
        $apiToken = $this->request->getParam('apitoken');
        $serviceId = $this->request->getParam('serviceid');
        $scope = $this->request->getParam('scope');
        $scopeId = $this->request->getParam('scopeid');

        $gateway = $this->scopeConfig->getValue('payment/paynl/failover_gateway', $scope, $scopeId);
      
        if (!empty($apiToken) && !empty($serviceId) && !empty($tokencode)) {
            try {
                if (!empty($gateway) && substr(trim($gateway), 0, 4) === "http") {
                    \Paynl\Config::setApiBase(trim($gateway));
                }
                \Paynl\Config::setTokenCode($tokencode);
                \Paynl\Config::setApiToken($apiToken);
                \Paynl\Config::setServiceId($serviceId);

                Paymentmethods::getList();
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else if (empty($apiToken) || empty($serviceId) || empty($tokencode)) {
            $error = __('Tokencode, API-token and Service-ID are required.');
        }

        switch ($error) {
            case 'HTTP/1.0 401 Unauthorized':
                $error = __('Service-ID, API-Token or Tokencode invalid');
                break;
            case 'PAY-404 - Service not found':
                $error = __('Service-ID is invalid.');
                break;
            case 'PAY-403 - Access denied: Token not valid for this company':
                $error = __('Service-ID / API-Token combination is invalid.');
                break;
        }

        if (!empty($error)) {
            $status = 0;
        }

        return $result->setData(['success' => true, 'status' => $status, 'error' => $error]);
    }
}
