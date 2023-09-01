<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ShowKvkOptions implements ArrayInterface
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
        '0' => __('No'),
        '1' => __('Yes, as optional'),
        '2' => __('Yes, as required'),
        ];
    }
}
