<?php

namespace Paynl\Payment\Model\Config\Source\Cse;

use \Magento\Framework\Option\ArrayInterface;

class PaymentPopupOptions implements ArrayInterface
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
            'popup_native' => __('Default Magento Popup'),
            'popup_custom' => __('Custom Popup (No close button)'),
        ];
    }

 
}