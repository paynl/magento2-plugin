<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Config;

class DefaultPaymentOption implements ArrayInterface
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
     * constructor.
     * @param Config $paymentConfig
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        Config $paymentConfig,
        ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfigInterface = $scopeConfigInterface;
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
        $activePaymentMethods = $this->paymentConfig->getActiveMethods();
        //get only PAY. Methods
        $active_paynl_methods = [];
        $active_paynl_methods[0] = __('None');
        foreach ($activePaymentMethods as $key => $value) {
            if (strpos($key, 'paynl') !== false && $key != 'paynl_payment_paylink') {
                $active_paynl_methods[$key] = $this->scopeConfigInterface->getValue('payment/' . $key . '/title');
            }
        }
        return $active_paynl_methods;
    }
}
