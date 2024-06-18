<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PinMoment implements ArrayInterface
{
    public const LOCATION_CHECKOUT = 0;
    public const LOCATION_PICKUP = 1;
    public const LOCATION_CHOICE = 2;

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
            '0' => __("Direct checkout payment (default)"),
            '1' => __("Payment takes place at the pickup location, only create a backorder"),
            '2' => __("Provide this choice in the checkout"),
        ];
    }
}
