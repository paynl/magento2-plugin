<?php

namespace Paynl\Payment\Model\Config\Source;

class ShowPaymentOptions extends PayOption
{
    public function __construct($options = array())
    {
        parent::__construct([
            0 => __('Off'),
            1 => __('On, as dropdown'),
            2 => __('On, as list'),
        ]);
    }
}
