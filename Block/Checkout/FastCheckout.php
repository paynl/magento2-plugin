<?php

namespace Paynl\Payment\Block\Checkout;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Item\Block;
use Magento\Framework\View\Page\Config;

class FastCheckout extends Block
{
    /**
     * @param Context $context
     * @param Config $page
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $page,
        array $data = []
    ) {
        $page->addPageAsset('Paynl_Payment::css/payFastCheckout.css');
        parent::__construct($context, $data);
    }
}
