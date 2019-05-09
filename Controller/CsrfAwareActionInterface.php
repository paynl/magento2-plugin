<?php

namespace Paynl\Payment\Controller;

//workaround to make sure this works in magento < 2.3

if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
    interface CsrfAwareActionInterface extends \Magento\Framework\App\CsrfAwareActionInterface
    {
    }
} else {
    interface CsrfAwareActionInterface
    {
    }
}