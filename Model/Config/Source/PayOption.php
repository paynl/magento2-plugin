<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PayOption implements ArrayInterface
{
    protected $options = array();

    /**
     * PayOption constructor.
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrResult = [];
        foreach ($this->options as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }
}
