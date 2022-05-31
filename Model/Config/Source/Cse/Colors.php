<?php

namespace Paynl\Payment\Model\Config\Source\Cse;

use Paynl\Payment\Model\Config\Source\PayOption;

class Colors extends PayOption
{
    public function __construct($options = array())
    {
        $options = [
            'borders' => __('Coloured Borders'),
            'background' => __('Background'),
            'none' => __('No Colours'),
        ];
        parent::__construct($options);
    }
}
