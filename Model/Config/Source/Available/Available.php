<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source\Available;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use \Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\Factory as PaymentMethodFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\Storage\WriterInterface;
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

    /** 
     * @var  Store 
     * */
    private $store;   

    /** 
     * @var  ConfigWriter 
     * */
    private $configWriter;
    
    /** 
     * @var  CacheTypeList 
     * */
    private $cacheTypeList;

    public function __construct(
        Config $config,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        PaymentMethodFactory $paymentMethodFactory,
        Store $store,  
        WriterInterface $configWriter,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    )
    {
        $this->_config = $config;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_paymentmethodFactory = $paymentMethodFactory;
        $this->store = $store;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
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
            $this->setDefaults($list);

            if (isset($list[$paymentOptionId])) {
                return true;
            }
        }

        return false;
    }

    public function setDefaults($Paymentmethods){        
        
        $Magento_paymentmethod_codes = array(
            "0"=>"paylink",
            "739"=>"afterpay",
            "2080"=>"alipay",
            "1705"=>"amex",
            "2277"=>"applepay",
            "1672"=>"billink",
            "1744"=>"capayable",
            "1813"=>"capayable_gespreid",
            "1945"=>"cartasi",
            "710"=>"cartebleue",
            "1981"=>"cashly",
            "139"=>"clickandbuy",
            "2107"=>"creditclick",
            "1939"=>"dankort",
            "2062"=>"eps",
            "815"=>"fashioncheque",
            "1699"=>"fashiongiftcard",
            "1702"=>"focum",
            "812"=>"gezondheidsbon",
            "694"=>"giropay",
            "1657"=>"givacard",
            "2283"=>"huisentuincadeau",
            "10"=>"ideal",
            "1729"=>"instore",
            "1717"=>"klarna",
            "2265"=>"klarnakp",
            "712"=>"maestro",
            "436"=>"mistercash",
            "2271"=>"multibanco",
            "1588"=>"mybank",
            "136"=>"overboeking",
            "2379"=>"payconiq",
            "138"=>"paypal",
            "553"=>"paysafecard",
            "816"=>"podiumcadeaukaart",
            "707"=>"postepay",
            "2151"=>"przelewy24",
            "559"=>"sofortbanking",
            "1987"=>"spraypay",
            "1600"=>"telefonischbetalen",
            "2104"=>"tikkie",
            "706"=>"visamastercard",
            "1714"=>"vvvgiftcard",
            "811"=>"webshopgiftcard",
            "1978"=>"wechatpay",
            "1666"=>"wijncadeau",
            "1877"=>"yehhpay",
            "1645"=>"yourgift"
        );

        $changed = false;

        foreach ($Paymentmethods as $key => $method) {
            
            if(isset($Magento_paymentmethod_codes[$method['id']])){
                $name = $Magento_paymentmethod_codes[$method['id']];

                if(strlen($this->store->getConfig('payment/paynl_payment_'.$name.'/min_order_total')) == 0 && isset($method['min_amount'])){
                    $this->configWriter->save('payment/paynl_payment_'.$name.'/min_order_total', $method['min_amount']);
                    if(strlen($method['min_amount']) > 0){$changed = true;}
                }

                if(strlen($this->store->getConfig('payment/paynl_payment_'.$name.'/max_order_total')) == 0 && isset($method['max_amount'])){
                    $this->configWriter->save('payment/paynl_payment_'.$name.'/max_order_total', $method['max_amount']);
                    if(strlen($method['max_amount']) > 0){$changed = true;}
                }

                if(strlen($this->store->getConfig('payment/paynl_payment_'.$name.'/instructions')) == 0 && isset($method['brand']['public_description'])){
                    $this->configWriter->save('payment/paynl_payment_'.$name.'/instructions', $method['brand']['public_description']);
                    if(strlen($method['brand']['public_description']) > 0){$changed = true;}
                }
            }            
        }
        
        //Clean the cache or esle it won't show the changes
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

        //Refresh the page to apply the defaults after opening Payment methodes
        if($changed){
            header("Refresh:0");
        }

    }
}
