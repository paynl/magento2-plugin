<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model;

use Magento\Store\Model\Store;
use \Paynl\Paymentmethods;


/**
 * Description of Config
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Config
{

    /** @var  Store */
    private $store;
    private $resources;

    public function __construct(
        Store $store,
        \Magento\Framework\View\Element\Template $resources
    )
    {
        $this->store = $store;
        $this->resources = $resources;
    }

    public function getVersion()
    {
        return '1.6.3';
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
        $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $ipconfig = $this->store->getConfig('payment/paynl/testipaddress');
        $allowed_ips = explode(',', $ipconfig);
        if (in_array($ip, $allowed_ips) && filter_var($ip, FILTER_VALIDATE_IP) && strlen($ip) > 0 && count($allowed_ips) > 0) {
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

    /**
     * @param $methodCode string
     * @return string
     */
    public function getSuccessPage($methodCode)
    {
        $success_page = $this->store->getConfig('payment/' . $methodCode . '/custom_success_page');
        if (empty($success_page)) $success_page = 'checkout/onepage/success';

        return $success_page;
    }

    /**
     * Configures the sdk with the API token and serviceId
     *
     * @return bool TRUE when config loaded, FALSE when the apitoken or serviceId are empty
     */
    public function configureSDK()
    {
        $apiToken = $this->getApiToken();
        $serviceId = $this->getServiceId();
        $tokencode = $this->getTokencode();

        if (!empty($tokencode)) {
            \Paynl\Config::setTokenCode($tokencode);
        }

        if (!empty($apiToken) && !empty($serviceId)) {
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

    public function getIconUrl($PaymentMethodeID)
    {      
        $iconUrl = 'https://static.pay.nl/payment_profiles/50x32/#paymentOptionId#.png';
        $configured = $this->configureSDK();
        if ($configured) {
            $list = Paymentmethods::getList();
            if (isset($list[$PaymentMethodeID])) {
                $iconUrl = $this->resources->getViewFileUrl("Paynl_Payment::logos/" . $list[$PaymentMethodeID]['brand']['id'] . ".png");
            }
        }

        return empty($iconUrl) ? $url : $iconUrl;
    }

    public function getCancelURL()
    {
        return $this->store->getConfig('payment/paynl/cancelurl');
    }
}
