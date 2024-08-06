<?php

namespace Paynl\Payment\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;

class FastCheckout implements ArgumentInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * BuyNow constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $session
    ) {
        $this->storeManager = $storeManager;
        $this->session = $session;
    }

    /**  
     * @return string
     */
    public function getVisibility()
    {
        $store = $this->storeManager->getStore();
        if($this->session->isLoggedIn() && $store->getConfig('payment/paynl_payment_ideal/fast_checkout_guest_only') == 1) {
            return false;
        }
        return true;     
    }
}
