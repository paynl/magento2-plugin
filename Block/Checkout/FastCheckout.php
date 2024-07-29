<?php

namespace Paynl\Payment\Block\Checkout;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Item\Block;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\Store;

class FastCheckout extends Block
{
    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * ListProduct constructor.
     * @param Context $context
     * @param UrlHelper $urlHelper
     * @param Config $page
     * @param Store $store
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlHelper $urlHelper,
        Config $page,
        Store $store,
        array $data = []
    ) {
        $this->urlHelper = $urlHelper;
        $page->addPageAsset('Paynl_Payment::css/payFastCheckout.css');
        parent::__construct($context, $data);
    }
}
