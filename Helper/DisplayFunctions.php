<?php

use Magento\Framework\App\ObjectManager;
use Paynl\Payment\Helper\PayHelper;

if (!function_exists('displayPayRequest')) {
    function displayPayRequest($uri, $body, $response, $curlRequest)
    {
        /** @var PayHelper $payHelper */
        $payHelper = ObjectManager::getInstance()->get(PayHelper::class);
        $payHelper->logDev('SDK call: ' . $uri . PHP_EOL .
            '=> response: ' . substr($response, 0, 2000) . PHP_EOL . PHP_EOL);
    }
}

