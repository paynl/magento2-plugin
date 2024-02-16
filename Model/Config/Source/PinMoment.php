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
            '0' => __("Only provide direct payment option (default)"),
            '1' => __("Only use payment when instore pickup is selected"),
            '2' => __("Provide option to customer"),
        ];
    }
}
