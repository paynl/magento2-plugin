<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Paynl\Payment\Model\Config;

/**
 * Description of AbstractPaymentMethod
 *
 * @author Andy Pieters <andy@pay.nl>
 */
abstract class PaymentMethod extends AbstractMethod
{
    protected $_isInitializeNeeded = true;

    protected $_canRefund = false;

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    public function startTransaction(Order $order, UrlInterface $url)
    {
        $config = new Config($this->_scopeConfig);

        $config->configureSDK();

        $total = $order->getGrandTotal();
        $items = $order->getAllVisibleItems();

        $orderId = $order->getIncrementId();
        $quoteId = $order->getQuoteId();

        $currency = $order->getOrderCurrencyCode();

        $returnUrl = $url->getUrl('paynl/checkout/finish/');
        $exchangeUrl = $url->getUrl('paynl/checkout/exchange/');

        $paymentOptionId = $this->getPaymentOptionId();


        $arrBillingAddress = $order->getBillingAddress();
        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();


            $enduser = array(
                'initials' => substr($arrBillingAddress['firstname'], 0, 1),
                'lastName' => $arrBillingAddress['lastname'],
                'phoneNumber' => $arrBillingAddress['telephone'],
                'emailAddress' => $arrBillingAddress['email'],
            );

            $invoiceAddress = array(
                'initials' => substr($arrBillingAddress['firstname'], 0, 1),
                'lastName' => $arrBillingAddress['lastname']
            );

            $arrAddress = \Paynl\Helper::splitAddress($arrBillingAddress['street']);
            $invoiceAddress['streetName'] = $arrAddress[0];
            $invoiceAddress['houseNumber'] = $arrAddress[1];
            $invoiceAddress['zipCode'] = $arrBillingAddress['postcode'];
            $invoiceAddress['city'] = $arrBillingAddress['city'];
            $invoiceAddress['country'] = $arrBillingAddress['country_id'];

        }

        $arrShippingAddress = $order->getShippingAddress();
        if ($arrShippingAddress) {
            $arrShippingAddress = $arrShippingAddress->toArray();


            $shippingAddress = array(
                'initials' => substr($arrShippingAddress['firstname'], 0, 1),
                'lastName' => $arrShippingAddress['lastname']
            );
            $arrAddress2 = \Paynl\Helper::splitAddress($arrShippingAddress['street']);
            $shippingAddress['streetName'] = $arrAddress2[0];
            $shippingAddress['houseNumber'] = $arrAddress2[1];
            $shippingAddress['zipCode'] = $arrShippingAddress['postcode'];
            $shippingAddress['city'] = $arrShippingAddress['city'];
            $shippingAddress['country'] = $arrShippingAddress['country_id'];

        }
        $data = array(
            'amount' => $total,
            'returnUrl' => $returnUrl,
            'paymentMethod' => $paymentOptionId,
            'description' => $orderId,
            'extra1' => $orderId,
            'extra2' => $quoteId,
            'exchangeUrl' => $exchangeUrl,
            'currency' => $currency,
        );
        if(isset($shippingAddress)){
            $data['address'] = $shippingAddress;
        }
        if(isset($invoiceAddress)) {
            $data['invoiceAddress'] = $invoiceAddress;
        }
        if(isset($enduser)){
            $data['enduser'] = $enduser;
        }
        $arrProducts = array();
        foreach ($items as $item) {
            $arrItem = $item->toArray();
            if ($arrItem['price_incl_tax'] != null) {
                // taxamount is not valid, because on discount it returns the taxamount after discount
                $taxAmount = $arrItem['price_incl_tax'] - $arrItem['price'];
                $product = array(
                    'id' => $arrItem['product_id'],
                    'name' => $arrItem['name'],
                    'price' => $arrItem['price_incl_tax'],
                    'qty' => $arrItem['qty_ordered'],
                    'tax' => $taxAmount,
                );
            }
            $arrProducts[] = $product;
        }

        //shipping
        $shippingCost = $order->getShippingInclTax();
        $shippingTax = $order->getShippingTaxAmount();
        $shippingDescription = $order->getShippingDescription();

        $arrProducts[] = array(
            'id' => 'shipping',
            'name' => $shippingDescription,
            'price' => $shippingCost,
            'qty' => 1,
            'tax' => $shippingTax
        );

        // kortingen
        $discount = $order->getDiscountAmount();
        $discountDescription = $order->getDiscountDescription();

        if ($discount != 0) {
            $arrProducts[] = array(
                'id' => 'discount',
                'name' => $discountDescription,
                'price' => $discount,
                'qty' => 1,
                'tax' => $order->getDiscountTaxCompensationAmount() * -1
            );
        }

        $data['products'] = $arrProducts;

        if ($config->isTestMode()) {
            $data['testmode'] = 1;
        }
        $data['ipaddress'] = $order->getRemoteIp();

        $transaction = \Paynl\Transaction::start($data);

        return $transaction->getRedirectUrl();
    }

    public function getPaymentOptionId()
    {
        return $this->getConfigData('payment_option_id');
    }
}