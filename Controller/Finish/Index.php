<?php
/*
 * Copyright (C) 2015 Pay.nl
 */

namespace Paynl\Payment\Controller\Finish;

/**
 * Description of Redirect
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    protected $_config;

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_config = $config;
        $this->_orderFactory = $orderFactory;

        parent::__construct($context);
    }

    private function _reorder($orderId)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);;
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /* @var $cart \Magento\Checkout\Model\Cart */
        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $cart->truncate();

        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($this->_objectManager->get('Magento\Checkout\Model\Session')->getUseNotice(true)) {
                    $this->messageManager->addNotice($e->getMessage());
                } else {
                    $this->messageManager->addError($e->getMessage());
                }
                return $resultRedirect->setPath('*/*/history');
            } catch (\Exception $e) {
                $this->messageManager->addException($e,
                    __('We can\'t add this item to your shopping cart right now.'));
                return $resultRedirect->setPath('checkout/cart');
            }
        }

        $cart->save();
        return $resultRedirect->setPath('checkout/cart');
    }

    public function execute()
    {
        \Paynl\Config::setApiToken($this->_config->getApiToken());

        $transaction = \Paynl\Transaction::getForReturn();

        if ($transaction->isPaid() || $transaction->isPending()) {
            $resultRedirect = $this->resultRedirectFactory->create();


            return $resultRedirect->setPath('checkout/onepage/success');
        } else {
            //canceled, reorder
            $this->messageManager->addNotice(__('Payment canceled'));
            return $this->_reorder($transaction->getDescription());
        }
    }
}