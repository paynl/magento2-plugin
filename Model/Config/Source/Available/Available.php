<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source\Available;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use \Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\Factory as PaymentMethodFactory;
use Magento\Store\Model\ScopeInterface;
use \Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use \Paynl\Paymentmethods;

abstract class Available implements ArrayInterface
{
    /**
     * @var string The name of the class for this method
     */
    protected $_class;

    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var PaymentMethodFactory
     */
    protected $_paymentmethodFactory;

    /**
     * @var Config
     */
    protected $_config;

    public function __construct(
        Config $config,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        PaymentMethodFactory $paymentMethodFactory
    )
    {
        $this->_config = $config;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_paymentmethodFactory = $paymentMethodFactory;
    }


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptions = $this->toArray();

        $arrResult = [];
        foreach ($arrOptions as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $configured = $this->configureSDK();
        if (!$configured) {
            return [0 => __('Enter your API-token and ServiceId first')];
        }
        try {
            if ($this->_isAvailable()) {
                return [0 => __('No'), 1 => __('Yes')];
            } else {
                return [0 => __('Not available, you can enable this on admin.pay.nl')];
            }
        } catch (\Exception $e) {
            return [0 => 'Error: ' . $e->getMessage()];
        }

    }

    protected function configureSDK()
    {
        $apiToken = trim($this->getConfigValue('payment/paynl/apitoken'));
        $serviceId = trim($this->getConfigValue('payment/paynl/serviceid'));
        $tokencode = trim($this->getConfigValue('payment/paynl/tokencode'));

        if(! empty($tokencode)) {
            \Paynl\Config::setTokenCode($tokencode);
        }

        if (!empty($apiToken) && !empty($serviceId)) {
            \Paynl\Config::setApiToken($apiToken);
            \Paynl\Config::setServiceId($serviceId);

            return true;
        }

        return false;
    }

    protected function getPaymentOptionId()
    {
        $method = $this->_paymentmethodFactory->create($this->_class);
        if($method instanceof PaymentMethod){
            return $method->getPaymentOptionId();
        }
        return null;
    }


    protected function getConfigValue($path)
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeValue = null;

        $store = $this->_request->getParam('store');
        $website = $this->_request->getParam('website');
        if ($store) {
            $scopeValue = $store;
            $scopeType = ScopeInterface::SCOPE_STORE;
        } elseif ($website) {
            $scopeValue = $website;
            $scopeType = ScopeInterface::SCOPE_WEBSITE;
        }


        return $this->_scopeConfig->getValue($path, $scopeType, $scopeValue);
    }

    protected function _isAvailable()
    {
        $configured = $this->configureSDK();
        if ($configured) {
            $paymentOptionId = $this->getPaymentOptionId();

            $list = Paymentmethods::getList();

            if (isset($list[$paymentOptionId])) {
                return true;
            }
        }

        return false;
    }
}
