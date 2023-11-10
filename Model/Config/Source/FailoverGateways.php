<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class FailoverGateways implements ArrayInterface
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
            'https://rest-api.pay.nl' => __('Pay.nl (Default)'),
            'https://rest.achterelkebetaling.nl' => __('rest.achterelkebetaling.nl'),
            'https://rest.payments.nl' => __('rest.payments.nl'),
            'custom' => __('Custom')
        ];
    }
}
