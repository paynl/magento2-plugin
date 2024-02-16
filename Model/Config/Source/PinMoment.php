<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PinMoment implements ArrayInterface
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
        return [

            '0' => __("Directly payment initialization(default)"),
            '1' => __("Let payment take place at the pickup location, only create a backorder"),
            '2' => __("Show a selection for the checkout-user to choose between direct payment or payment at location"),


//            '0' => __("Directly payment initialization(default)"),
//            '1' => __("Let payment take place at the pickup location, only create a backorder"),
//            '2' => __("Show a selection for the checkout-user to choose between direct payment or payment at location"),
        ];
    }
}
