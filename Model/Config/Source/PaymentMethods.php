<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\PaymentMethodList;

class PaymentMethods implements ArrayInterface
{
    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var PaymentMethodList
     */
    protected $paymentMethodList;

    /**
     * constructor.
     * @param Config $paymentConfig
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param PaymentMethodList $paymentMethodList
     */
    public function __construct(
        Config $paymentConfig,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfigInterface,
        PaymentMethodList $paymentMethodList
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->request = $request;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->paymentMethodList = $paymentMethodList;
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
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        $scope = 'default';
        $scopeId = 0;

        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        }
        if ($websiteId) {
            $scope = 'websites';
            $scopeId = $websiteId;
        }

        $paymentMethods = $this->scopeConfigInterface->getValue('payment', $scope, $scopeId);

        # Get only active PAY. Methods
        $active_paynl_methods = [];

        foreach ($paymentMethods as $key => $method) {
            if (
                isset($method['active']) &&
                $method['active'] == 1 &&
                strpos($key, 'paynl') !== false &&
                $key != 'paynl_payment_paylink' &&
                $key != 'paynl_payment_cardrefund'
            ) {
                $active_paynl_methods[$key] = $this->scopeConfigInterface->getValue('payment/' . $key . '/title', $scope, $scopeId);
            }
        }

        return $active_paynl_methods;
    }

}