<?php

namespace Paynl\Payment\Controller\Order;

class Finish extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    /**
     * Finish construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    /**
     * @return object
     */
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
