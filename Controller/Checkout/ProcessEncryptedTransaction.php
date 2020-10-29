<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Error\Error;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\Visamastercard;
use Psr\Log\LoggerInterface;

/**
 * Description of ProcessEncryptedTransaction
 *
 * @author Michael Roterman <michael@pay.nl>
 */
class ProcessEncryptedTransaction extends PayAction
{
    /**
     * @var Config
     */
	private $config;

    /**
     * @var Session
     */
	private $checkoutSession;

    /**
     * @var LoggerInterface
     */
	private $_logger;

    /**
     * @var PaymentHelper
     */
	private $paymentHelper;

	/**
	 * @var QuoteRepository
	 */
	private $quoteRepository;

    /**
     * @var Data
     */
	private $jsonHelper;

	/**
	 * @var OrderRepository
	 */
	private $orderRepository;

    /**
     * @var StoreManagerInterface
     */
	private $storageManager;

    /**
     * ProcessEncryptedTransaction constructor.
     * @param Context $context
     * @param Config $config
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param Data $jsonHelper
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        LoggerInterface $logger,
        PaymentHelper $paymentHelper,
		QuoteRepository $quoteRepository,
		OrderRepository $orderRepository,
        StoreManagerInterface $storeManager,
        Data $jsonHelper
    )
    {
        $this->config          = $config; // PAY. config helper
        $this->checkoutSession = $checkoutSession;
        $this->_logger         = $logger;
        $this->paymentHelper   = $paymentHelper;
		$this->quoteRepository = $quoteRepository;
		$this->orderRepository = $orderRepository;
		$this->storageManager  = $storeManager;
		$this->jsonHelper      = $jsonHelper;

        parent::__construct($context);
    }

    /**
     * Process the encrypted transaction.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();

            if(empty($order)){
                throw new Error('No order found in session, please try again');
            }

          # Restore the quote
          $quote = $this->quoteRepository->get($order->getQuoteId());
          $quote->setIsActive(true)->setReservedOrderId(null);
          $this->checkoutSession->replaceQuote($quote);
          $this->quoteRepository->save($quote);

          $payment = $order->getPayment();

            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());

          // Only allow visamastercard class as this is the only one that supports this behavior
            if ($methodInstance instanceof Visamastercard) {
                $this->_logger->notice('PAY.: Start new encrypted payment for order ' . $order->getId());
                $returnUrl = $this->storageManager->getStore()->getBaseUrl() . 'paynl/checkout/returnEncryptedTransaction';
                $paymentCompleteUrl = $this->storageManager->getStore()->getBaseUrl() . 'paynl/checkout/finish';
                $encryptedTransaction = $methodInstance->startEncryptedTransaction(
                    $order,
                    $this->getRequest()->getParam('pay_encrypted_data'),
                    array(
                        'returnUrl' => $returnUrl . '?' . http_build_query(array(
                            'payment_complete_url' => $paymentCompleteUrl
                        ))
                    )
                );
                $this->getResponse()->setNoCacheHeaders();
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(
                        $encryptedTransaction
                    )
                );
            } else {
              throw new Error('PAY.: Method is not a paynl payment method');
            }
        } catch (Exception $e) {
            $this->_getCheckoutSession()->restoreQuote();
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_logger->critical($e);

            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode(array(
                    'type' => 'error',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ))
            );
        }
    }

    /**
     * Return checkout session object
     *
     * @return Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
