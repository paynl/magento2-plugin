<?php

namespace Paynl\Payment\Helper;

class PayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getClientIp()
    {
        $ipforward = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        return !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ipforward;
    }
}
