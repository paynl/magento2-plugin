<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ShowDobOptions implements ArrayInterface
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
            '0' => __('Don\'t show at payment method.'),
            '1' => __('Show DOB field at method, but leave it optional.'),
            '2' => __('Show DOB field at method, make it required.'),
            '3' => __('Don\'t show DOB field at method, but set as required. Use DOB field from checkout instead.'),
        ];
    }
}
