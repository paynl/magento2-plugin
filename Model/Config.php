<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Description of Config
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfigInterface;

    public function __construct(
    ScopeConfigInterface $configInterface
    )
    {
        $this->_scopeConfigInterface = $configInterface;
    }
    public function getApiToken()
    {
        return $this->_scopeConfigInterface->getValue('payment/paynl/apitoken', 'store');
    }

    public function getServiceId()
    {
        return $this->_scopeConfigInterface->getValue('payment/paynl/serviceid', 'store');
    }

    public function isTestMode()
    {
       return $this->_scopeConfigInterface->getValue('payment/paynl/testmode', 'store') == 1;
    }
	public function isNeverCancel()
	{
		return $this->_scopeConfigInterface->getValue('payment/paynl/never_cancel', 'store') == 1;
	}

	public function isAlwaysBaseCurrency(){
        return $this->_scopeConfigInterface->getValue('payment/paynl/always_base_currency', 'store') == 1;
    }

    public function getLanguage(){
        $language = $this->_scopeConfigInterface->getValue('payment/paynl/language', 'store');
        return $language?$language:'nl'; //default nl
    }

    public function getPaymentOptionId($methodCode){
        return $this->_scopeConfigInterface->getValue('payment/'.$methodCode.'/payment_option_id', 'store');
    }

    /**
     * Configures the sdk with the API token and serviceId
     *
     * @return bool TRUE when config loaded, FALSE when the apitoken or serviceId are empty
     */
    public function configureSDK(){
        $apiToken = $this->getApiToken();
        $serviceId = $this->getServiceId();

        if(!empty($apiToken) && !empty($serviceId)){
            \Paynl\Config::setApiToken($apiToken);
            \Paynl\Config::setServiceId($serviceId);
            return true;
        }
        return false;
    }
}