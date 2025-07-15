<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class InvoiceCreation implements ArrayInterface
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
            '1' => __('Create invoice upon payment (default)'),
            '0' => __('Create invoice upon shipment'),
        ];
    }
}