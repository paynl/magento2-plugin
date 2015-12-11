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
     * @var \Paynl\Payment\Model\Config
     */
    protected $_config;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config
    )
    {
        $this->_config = $config; // Pay.nl config helper

        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Checkout\Model\Type\Onepage $onepage */
        $onepage = $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $onepage->getQuote();

        $quote->collectTotals();

        $quote->reserveOrderId();

        $orderId = $quote->getReservedOrderId();

        $payment = $quote->getPayment()->getMethodInstance();

        $total = $quote->getGrandTotal();
        $items = $quote->getAllVisibleItems();

        $currency = $quote->getQuoteCurrencyCode();

        $returnUrl = $this->_url->getUrl('paynl/finish/');
        $exchangeUrl = $this->_url->getUrl('paynl/exchange/');

        $paymentOptionId = $payment->getPaymentOptionId();


        $arrBillingAddress = $quote->getBillingAddress()->toArray();

        $arrShippingAddress = $quote->getShippingAddress()->toArray();

        $enduser = array(
            'initials' => substr($arrBillingAddress['firstname'], 0, 1),
            'lastName' => $arrBillingAddress['lastname'],
            'phoneNumber' => $arrBillingAddress['telephone'],
            'emailAddress' => $arrBillingAddress['email'],
        );

        $address = array();
        $arrAddress = \Paynl\Helper::splitAddress($arrBillingAddress['street']);
        $address['streetName'] = $arrAddress[0];
        $address['houseNumber'] = $arrAddress[1];
        $address['zipCode'] = $arrBillingAddress['postcode'];
        $address['city'] = $arrBillingAddress['city'];
        $address['country'] = $arrBillingAddress['country_id'];

        $shippingAddress = array();
        $arrAddress2 = \Paynl\Helper::splitAddress($arrShippingAddress['street']);
        $shippingAddress['streetName'] = $arrAddress2[0];
        $shippingAddress['houseNumber'] = $arrAddress2[1];
        $shippingAddress['zipCode'] = $arrShippingAddress['postcode'];
        $shippingAddress['city'] = $arrShippingAddress['city'];
        $shippingAddress['country'] = $arrShippingAddress['country_id'];

        $data = array(
            'amount' => $total,
            'returnUrl' => $returnUrl,
            'paymentMethod' => $paymentOptionId,
            'description' => $orderId,
            'exchangeUrl' => $exchangeUrl,
            'currency' => $currency,
        );
        $data['address'] = $address;
        $data['shippingAddress'] = $shippingAddress;

        $data['enduser'] = $enduser;
        $arrProducts = array();
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
        if ($this->_config->isTestMode()) {
            $data['testmode'] = 1;
        }
        $data['ipaddress'] = $quote->getRemoteIp();

        \Paynl\Config::setApiToken($this->_config->getApiToken());
        \Paynl\Config::setServiceId($this->_config->getServiceId());

        $transaction = \Paynl\Transaction::start($data);

        $onepage->saveOrder();

        $this->_redirect($transaction->getRedirectUrl());
    }

}