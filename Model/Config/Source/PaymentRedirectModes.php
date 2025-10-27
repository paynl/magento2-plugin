<?php

namespace Paynl\Payment\Model\Config\Source;

use Paynl\Payment\Model\Config\Source\PayOption;

class PaymentRedirectModes extends PayOption
{
    public function __construct($options = array())
    {
        $options = [
            'get' => __('GET (Default)'),
            'session' => __('Session'),
        ];
        parent::__construct($options);
    }
}
