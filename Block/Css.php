<?php

namespace Paynl\Payment\Block;

use Magento\Framework\View\Page\Config;
use Magento\Store\Model\Store;

class Css extends \Magento\Backend\Block\AbstractBlock
{
    public function __construct(Config $page, Store $store)
    {
        if ($store->getConfig('payment/paynl/pay_style_checkout') == 2) {
            $page->addPageAsset('Paynl_Payment::css/paycheckoutstyle.css');
        } else {
            $page->addPageAsset('Paynl_Payment::css/paycheckout.css');
        }
    }
}
