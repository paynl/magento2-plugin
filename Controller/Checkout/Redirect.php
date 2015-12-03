<?php
/*
 * Copyright (C) 2015 Pay.nl
 */

namespace Paynl\Payment\Controller\Checkout;

/**
 * Description of Redirect
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = false;
  

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $_quoteManagement;

    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    protected $_config;
    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Paynl\Payment\Model\Config $config
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Checkout\Model\Session $checkoutSession,
    \Magento\Sales\Model\OrderFactory $orderFactory,
    \Magento\Quote\Api\CartManagementInterface $quoteManagement,
    \Paynl\Payment\Model\Config $config
    )
    {

        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory    = $orderFactory;
        $this->_quoteManagement = $quoteManagement;
        $this->_config = $config;

        parent::__construct($context);
    }

    /**
     * Return checkout quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function _getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
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

    /**
     * Instantiate quote and checkout
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initCheckout()
    {
        $quote = $this->_getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize the Checkout.'));
        }
    }

    public function execute()
    {
//        
        $this->_initCheckout();

        $this->_quote->collectTotals();

        $this->_quote->reserveOrderId();

        $orderId = $this->_quote->getReservedOrderId();

        $payment = $this->_quote->getPayment()->getMethodInstance();

        $total = $this->_quote->getGrandTotal();
        $items = $this->_quote->getAllVisibleItems();

        $currency = $this->_quote->getQuoteCurrencyCode();


        $returnUrl   = $this->_url->getUrl('paynl/finish/');
        $exchangeUrl = $this->_url->getUrl('paynl/exchange/');

        $paymentOptionId = $payment->getPaymentOptionId();

        $payment->initSettings();


        $arrBillingAddress  = $this->_quote->getBillingAddress()->toArray();
        $arrShippingAddress = $this->_quote->getShippingAddress()->toArray();

        $enduser = array(
            'initials' => substr($arrBillingAddress['firstname'], 0, 1),
            'lastName' => $arrBillingAddress['lastname'],
            'phoneNumber' => $arrBillingAddress['telephone'],
            'emailAddress' => $arrBillingAddress['email'],
        );

        $address                = array();
        $arrAddress             = \Paynl\Helper::splitAddress($arrBillingAddress['street']);
        $address['streetName']  = $arrAddress[0];
        $address['houseNumber'] = $arrAddress[1];
        $address['zipCode']     = $arrBillingAddress['postcode'];
        $address['city']        = $arrBillingAddress['city'];
        $address['country']     = $arrBillingAddress['country_id'];

        $shippingAddress                = array();
        $arrAddress2                    = \Paynl\Helper::splitAddress($arrShippingAddress['street']);
        $shippingAddress['streetName']  = $arrAddress2[0];
        $shippingAddress['houseNumber'] = $arrAddress2[1];
        $shippingAddress['zipCode']     = $arrShippingAddress['postcode'];
        $shippingAddress['city']        = $arrShippingAddress['city'];
        $shippingAddress['country']     = $arrShippingAddress['country_id'];

        $data                    = array(
            'amount' => $total,
            'returnUrl' => $returnUrl,
            'paymentMethod' => $paymentOptionId,
            'description' => $orderId,
            'exchangeUrl' => $exchangeUrl,
            'currency' => $currency,
        );
        $data['address']         = $address;
        $data['shippingAddress'] = $shippingAddress;

        $data['enduser'] = $enduser;
        $arrProducts     = array();
        foreach ($items as $item) {
            $arrItem = $item->toArray();
            if ($arrItem['price_incl_tax'] != null) {
                $product = array(
                    'id' => $arrItem['product_id'],
                    'name' => $arrItem['name'],
                    'price' => $arrItem['price_incl_tax'],
                    'qty' => $arrItem['qty'],
                    'tax' => $arrItem['tax_amount'],
                );
            }
            $arrProducts[] = $product;
        }

        $data['products'] = $arrProducts;
        if($this->_config->isTestMode()){
            $data['testmode'] = 1;
        }

        \Paynl\Config::setApiToken($this->_config->getApiToken());
        \Paynl\Config::setServiceId($this->_config->getServiceId());

        $transaction = \Paynl\Transaction::start($data);

        $this->_quoteManagement->placeOrder($this->_quote->getId(),
            $this->_quote->getPayment());

        $this->_redirect($transaction->getRedirectUrl());
    }
}