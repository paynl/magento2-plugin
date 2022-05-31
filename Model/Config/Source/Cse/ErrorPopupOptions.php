<?php

namespace Paynl\Payment\Model\Config\Source\Cse;

use Paynl\Payment\Model\Config\Source\PayOption;

class ErrorPopupOptions extends PayOption
{
    public function __construct($options = array())
    {
        $options = [
            'popup_native' => __('Default Magento Popup'),
            'inline' => __('Inline'),
        ];
        parent::__construct($options);
    }
}
