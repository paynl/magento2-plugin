<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class AuthorisedPaidAmount implements ArrayInterface
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
    public function toArray(): array
    {
        return [
        '0' => __('Do nothing (default)'),
        '1' => __('Set paid amount with order amount for captured payments'),
        ];
    }
}
