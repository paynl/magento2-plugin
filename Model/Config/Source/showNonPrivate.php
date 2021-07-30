<?php
/**
 * Copyright © 2020 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class showNonPrivate implements ArrayInterface
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
      '0' => __('Off'),
      '1' => __('Optional for business customers'),
      '2' => __('Required for business-customers'),
    ];
  }

}
