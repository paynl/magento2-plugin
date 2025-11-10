<?php

namespace Paynl\Payment\Block\Checkout;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Item\Block;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\StoreManagerInterface;

class FastCheckout extends Block
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Config $page
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $page,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;

        if ($this->isCssEnabled()) {
            $page->addPageAsset('Paynl_Payment::css/payFastCheckout.css');
        }
        
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    protected function isCssEnabled()
    {
        $store = $this->storeManager->getStore();
        return $store->getConfig('payment/paynl_payment_ideal/fast_checkout_css_enabled') == 1;
    }
}
