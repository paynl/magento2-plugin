<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use \Magento\Payment\Model\Method\AbstractMethod;
/**
 * Description of AbstractPaymentMethod
 *
 * @author Andy Pieters <andy@pay.nl>
 */
abstract class PaymentMethod extends AbstractMethod
{
    protected $_isInitializeNeeded = true;

    protected $_canRefund = false;
    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }


    public function initSettings()
    {

        $storeId = $this->getStore();

        $apitoken  = $this->_scopeConfig->getValue('payment/paynl/apitoken',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $serviceId = $this->_scopeConfig->getValue('payment/paynl/serviceid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        \Paynl\Config::setApitoken($apitoken);
        \Paynl\Config::setServiceId($serviceId);
    }

    public function getPaymentOptionId(){
        return $this->getConfigData('payment_option_id');
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

}