<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model;

use Magento\Store\Model\Store;

/**
 * Description of Config
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Config
{
    const FINISH_PAYLINK = 'paynl/checkout/paylink';
    const FINISH_STANDARD = 'checkout/onepage/success';
    const ORDERSTATUS_PAID = 100;
    const ORDERSTATUS_DENIED = -63;

    /** @var  Store */
    private $store;

    /** @var  Resources */
    private $resources;

    public function __construct(
        Store $store,
        \Magento\Framework\View\Element\Template $resources
    ) {
        $this->store = $store;
        $this->resources = $resources;
    }

    /**
     * @param string $defaultValue
     * @return mixed|string
     */
    public function getVersion($defaultValue = '')
    {
        $composerFilePath = sprintf('%s/%s', rtrim(__DIR__, '/'), '../composer.json');
        if (file_exists($composerFilePath)) {
            $composer = json_decode(file_get_contents($composerFilePath), true);

            if (isset($composer['version'])) {
                return $composer['version'];
            }
        }

        return $defaultValue;
    }

    public function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();

        return $version;
    }

    public function getPHPVersion()
    {
        return substr(phpversion(), 0, 3);
    }

    /**
     * @param Store $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    public function isSkipFraudDetection()
    {
        return $this->store->getConfig('payment/paynl/skip_fraud_detection') == 1;
    }

    public function isTestMode()
    {
        $remoteIP =  isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $remoteIP;

        $ipconfig = $this->store->getConfig('payment/paynl/testipaddress');
        $allowed_ips = explode(',', $ipconfig);
        if(in_array($ip, $allowed_ips) && filter_var($ip, FILTER_VALIDATE_IP) && strlen($ip) > 0 && count($allowed_ips) > 0){
            return true;
        }
        return $this->store->getConfig('payment/paynl/testmode') == 1;
    }

    public function isSendDiscountTax()
    {
        return $this->store->getConfig('payment/paynl/discount_tax') == 1;
    }

    public function isNeverCancel()
    {
        return $this->store->getConfig('payment/paynl/never_cancel') == 1;
    }

    public function isAlwaysBaseCurrency()
    {
        return $this->store->getConfig('payment/paynl/always_base_currency') == 1;
    }

    public function useMagOrderAmountForAuth()
    {
        return $this->store->getConfig('payment/paynl/use_magorder_for_auth') == 1;
    }

    public function getLanguage()
    {
        $language = $this->store->getConfig('payment/paynl/language');

        return $language ? $language : 'nl'; //default nl
    }

    public function getPaymentOptionId($methodCode)
    {
        return $this->store->getConfig('payment/' . $methodCode . '/payment_option_id');
    }

    public function getPendingStatus($methodCode)
    {
        return $this->store->getConfig('payment/' . $methodCode . '/order_status');
    }

    public function getAuthorizedStatus($methodCode)
    {
        return $this->store->getConfig('payment/' . $methodCode . '/order_status_authorized');
    }

    public function getPaidStatus($methodCode)
    {
        return $this->store->getConfig('payment/' . $methodCode . '/order_status_processing');
    }

    public function ignoreB2BInvoice($methodCode)
    {
        return $this->store->getConfig('payment/' . $methodCode . '/turn_off_invoices_b2b') == 1;
    }

    public function autoCaptureEnabled()
    {
       return $this->store->getConfig('payment/paynl/auto_capture') == 1;
    }

    /**
     * @param $methodCode string
     * @return string
     */
    public function getSuccessPage($methodCode)
    {
        return $this->store->getConfig('payment/' . $methodCode . '/custom_success_page');
    }

    /**
     * Configures the sdk with the API token and serviceId
     *
     * @return bool TRUE when config loaded, FALSE when the apitoken or serviceId are empty
     */
    public function configureSDK()
    {
        $apiToken  = $this->getApiToken();
        $serviceId = $this->getServiceId();
        $tokencode = $this->getTokencode();

        if(! empty($tokencode)) {
            \Paynl\Config::setTokenCode($tokencode);
        }

        if ( ! empty($apiToken) && ! empty($serviceId)) {
            \Paynl\Config::setApiToken($apiToken);
            \Paynl\Config::setServiceId($serviceId);

            return true;
        }

        return false;
    }

    public function getApiToken()
    {
        return trim($this->store->getConfig('payment/paynl/apitoken'));
    }

    public function getTokencode()
    {
        return trim($this->store->getConfig('payment/paynl/tokencode'));
    }

    public function getServiceId()
    {
        return trim($this->store->getConfig('payment/paynl/serviceid'));
    }

    public function getIconUrl($methodCode)
    {
        $iconUrl = 'https://static.pay.nl/payment_profiles/50x32/#paymentOptionId#.png';

        $brandId = $this->store->getConfig('payment/' . $methodCode . '/brand_id');
        if(!empty($brandId)) {
            $iconUrl = $this->resources->getViewFileUrl("Paynl_Payment::logos/" . $brandId . ".png");
        }

        return $iconUrl;
    }

    public function getUseAdditionalValidation()
    {
        return trim($this->store->getConfig('payment/paynl/use_additional_validation'));
    }

    public function getCancelURL()
    {
        return $this->store->getConfig('payment/paynl/cancelurl');
    }

    public function getDefaultPaymentOption()
    {
        return $this->store->getConfig('payment/paynl/default_payment_option');
    }

    public function registerPartialPayments()
    {
        return $this->store->getConfig('payment/paynl/register_partial_payments');
    }

    public function getPaymentmethodCode($paymentProfileId){

        //Get all PAY. methods
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $paymentHelper = $objectManager->get('Magento\Payment\Helper\Data');
        $paymentMethodList = $paymentHelper->getPaymentMethods();
        $pay_methods = array();
        foreach ($paymentMethodList as $key => $value) {
            if (strpos($key, 'paynl_') !== false && $key != 'paynl_payment_paylink') {
                $code = $this->store->getConfig('payment/' . $key . '/payment_option_id');
                if($code == $paymentProfileId){
                    return $key;
                }
            }
        }
    }
}
