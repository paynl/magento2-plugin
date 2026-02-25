<?php

namespace Paynl\Payment\Controller\Order;

class Confirm extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $checkoutSession;

    /**
     * Pending construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        return parent::__construct($context);
    }

    /**
     * @return object|void
     */
    public function execute()
    {
        if (empty($this->checkoutSession->getLastRealOrderId())) {
            $this->_redirect('checkout/cart');
            return;
        }
        return $this->_pageFactory->create();
    }
}
