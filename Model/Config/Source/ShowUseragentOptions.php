<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ShowUseragentOptions implements ArrayInterface
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
            'No' => __('No'),
            'Chrome' => __('Google Chrome'),
            'Safari' => __('Safari'),
            'MSIE' => __('Internet Explorer'),
            'Opera' => __('Opera'),
            'Firefox' => __('Firefox'),
            'Edg' => __('Edge'),
            'Custom' => __('Custom')
        ];
    }
}
