<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Paynl_Payment',
    __DIR__
);

$functionFile = __DIR__ . '/Helper/DisplayFunctions.php';
if (file_exists($functionFile)) {
    require_once $functionFile;
}
