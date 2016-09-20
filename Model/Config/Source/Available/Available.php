<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source\Available;

use \Magento\Framework\Option\ArrayInterface;
use\Paynl\Payment\Model\Config;
use \Paynl\Paymentmethods;

abstract class Available implements ArrayInterface
{
    /**
     * The payment method code, should be the same as the code in the payment method model
     *
     * @var string
     */
    protected $_code;

    /**
     * @var Config
     */
    protected $_config;

    public function __construct(
        Config $config
    )
    {
        $this->_config = $config;
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
        $configured = $this->_config->configureSDK();
        if (!$configured) {
            return [0 => __('Enter your API-token and ServiceId first')];
        }
        try {
            if ($this->_isAvailable()) {
                return [0 => __('No'), 1 => __('Yes')];
            } else {
                return [0 => __('Not available, you can enable this on admin.pay.nl')];
            }
        } catch(\Exception $e){
            return [0 => 'Error: '.$e->getMessage()];
        }

    }

    protected function _isAvailable()
    {
        $configured = $this->_config->configureSDK();
        if ($configured) {
            $paymentOptionId = $this->_config->getPaymentOptionId($this->_code);

            $list = Paymentmethods::getList();

            if (isset($list[$paymentOptionId])) {
                return true;
            }
        }

        return false;
    }
}
