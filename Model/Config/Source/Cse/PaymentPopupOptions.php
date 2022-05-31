<?php

namespace Paynl\Payment\Model\Config\Source\Cse;

use Paynl\Payment\Model\Config\Source\PayOption;

class PaymentPopupOptions extends PayOption
{
    public function __construct($options = array())
    {
        $options = [
            'popup_native' => __('Default Magento Popup'),
            'popup_custom' => __('Custom Popup (No close button)'),
        ];
        parent::__construct($options);
    }
}
