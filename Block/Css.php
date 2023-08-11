<?php

namespace Paynl\Payment\Block;

use Magento\Framework\View\Page\Config;
use Magento\Store\Model\Store;

class Css extends \Magento\Backend\Block\AbstractBlock
{
    /**
     * @param Config $page
     * @param Store $store
     */
    public function __construct(Config $page, Store $store)
    {
        if ($store->getConfig('payment/paynl/pay_style_checkout') == 1) {
            $page->addPageAsset('Paynl_Payment::css/paycheckout.css');
        }
    }
}
