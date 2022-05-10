<?php

namespace Paynl\Payment\Block;

class Css extends \Magento\Backend\Block\AbstractBlock
{

    public function __construct()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        $page = $om->get(\Magento\Framework\View\Page\Config::class);
        $store = $om->get(\Magento\Store\Model\Store::class);

        if ($store->getConfig('payment/paynl/pay_style_checkout') == 1) {
            $page->addPageAsset('Paynl_Payment::css/paycheckout.css');
        }

        $cseEnabled = true;
        if ($cseEnabled === true) {
            $page->addPageAsset('Paynl_Payment::css/paycse.css');
        }

    }
}
