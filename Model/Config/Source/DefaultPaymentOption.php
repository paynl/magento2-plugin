<?php

namespace Paynl\Payment\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class DefaultPaymentOption implements ArrayInterface
{
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
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $paymentConfig = $objectManager->get(\Magento\Payment\Model\Config::class);
        $activePaymentMethods = $paymentConfig->getActiveMethods();
        $scopeConfigInterface = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        //get only PAY. Methods
        $active_paynl_methods = [];
        $active_paynl_methods[0] = 'None';
        foreach ($activePaymentMethods as $key => $value) {
            if (strpos($key, 'paynl') !== false && $key != 'paynl_payment_paylink') {
                $active_paynl_methods[$key] = $scopeConfigInterface->getValue('payment/' . $key . '/title');
            }
        }
        return $active_paynl_methods;
    }
}
