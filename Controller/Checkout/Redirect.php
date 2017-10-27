<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Paynl\Error\Error;

/**
 * Description of Redirect
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Paynl\Payment\Model\Config
     */
    protected $_config;


    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper
    )
    {
        $this->_config = $config; // Pay.nl config helper
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $order = $this->_getCheckoutSession()->getLastRealOrder();

            $method = $order->getPayment()->getMethod();

            $methodInstance = $this->_paymentHelper->getMethodInstance($method);
            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                $redirectUrl = $methodInstance->startTransaction($order);
                $this->_getCheckoutSession()->restoreQuote();
                $this->getResponse()->setNoCacheHeaders();
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                throw new Error('Method is not a paynl payment method');
            }

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_logger->critical($e);
            $this->_getCheckoutSession()->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
