<?php

namespace Paynl\Payment\Model\Config\Source\Cse;

use \Magento\Framework\Option\ArrayInterface;

class ErrorPopupOptions implements ArrayInterface
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
            'inline' => __('Inline'),
        ];
    }

    //const MODAL_POPUP_CUSTOM = 'popup_custom';
    //const MODAL_POPUP_NATIVE = 'popup_native';
    //const MODAL_POPUP_INLINE = 'inline';

}
