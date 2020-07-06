<?php

namespace Paynl\Payment\Controller;

# Workaround to make sure this works in magento < 2.3
if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
    include __DIR__ . "/CsrfInterface23.php";
} else {
    include __DIR__ . "/CsrfInterface22.php";
}
