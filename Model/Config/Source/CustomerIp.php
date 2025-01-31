<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CustomerIp implements ArrayInterface
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
            'default' => __('Default (Pay. SDK)'),
            'orderremoteaddress' => __('Magento Order IP'),
            'httpforwarded' => __('HTTP forwarded'),
            'remoteaddress' => __('Remote address')
        ];
    }
}
