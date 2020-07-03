<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Model\Config;
use Paynl\Transaction;

/**
 * Description of AbstractPaymentMethod
 *
 * @author Andy Pieters <andy@pay.nl>
 */
abstract class PaymentMethod extends AbstractMethod
{
    protected $_code = 'paynl_payment_base';


    protected $_isInitializeNeeded = true;

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_canCapture = true;

    protected $_canVoid = true;


    /**
     * @var Config
     */
    protected $paynlConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        \Magento\Sales\Model\Order\Config $orderConfig,
        OrderRepository $orderRepository,
        Config $paynlConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context, $registry, $extensionFactory, $customAttributeFactory,
            $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);

        $this->paynlConfig = $paynlConfig;
        $this->orderRepository = $orderRepository;
        $this->orderConfig = $orderConfig;
    }

    protected function getState($status)
    {
        $validStates = [
            Order::STATE_NEW,
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_HOLDED
        ];

        foreach ($validStates as $state) {
            $statusses = $this->orderConfig->getStateStatuses($state, false);
            if (in_array($status, $statusses)) return $state;
        }
        return false;
    }

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function getBanks()
    {
        return [];
    }

    public function getKVK()
    {
      return [];
    }

    public function getDOB()
    {
      return [];
    }

    public function initialize($paymentAction, $stateObject)
    {
        $status = $this->getConfigData('order_status');

        $stateObject->setState($this->getState($status));
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);

        $sendEmail = $this->_scopeConfig->getValue('payment/' . $this->_code . '/send_new_order_email', 'store');

        $payment = $this->getInfoInstance();
        /** @var Order $order */
        $order = $payment->getOrder();

        if ($sendEmail == 'after_payment') {
            //prevent sending the order confirmation
            $order->setCanSendNewEmailFlag(false);
        }

        $this->orderRepository->save($order);

        return parent::initialize($paymentAction, $stateObject);
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();

        Transaction::refund($transactionId, $amount);

        return $this;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();

        Transaction::capture($transactionId);

        return $this;
    }

    public function void(InfoInterface $payment)
    {
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();

        Transaction::void($transactionId);

        return $this;
    }

    public function startTransaction(Order $order)
    {
        $transaction = $this->doStartTransaction($order);
        $this->paynlConfig->setStore($order->getStore());

        $holded = $this->_scopeConfig->getValue('payment/' . $this->_code . '/holded', 'store');
        if ($holded) {
            $order->hold();
        }
        $this->orderRepository->save($order);

        return $transaction->getRedirectUrl();
    }

    protected function doStartTransaction(Order $order)
    {
        $this->paynlConfig->setStore($order->getStore());
        $this->paynlConfig->configureSDK();
        $additionalData = $order->getPayment()->getAdditionalInformation();
        $bankId = null;
        $expireDate = null;
        
        if (isset($additionalData['kvknummer']) && is_numeric($additionalData['kvknummer'])) {
            $kvknummer = $additionalData['kvknummer'];
        }
        if (isset($additionalData['bank_id']) && is_numeric($additionalData['bank_id'])) {
            $bankId = $additionalData['bank_id'];
        }
        if (isset($additionalData['valid_days']) && is_numeric($additionalData['valid_days'])) {
            $expireDate = new \DateTime('+' . $additionalData['valid_days'] . ' days');
        }

        if ($this->paynlConfig->isAlwaysBaseCurrency()) {
            $total = $order->getBaseGrandTotal();
            $currency = $order->getBaseCurrencyCode();
        } else {
            $total = $order->getGrandTotal();
            $currency = $order->getOrderCurrencyCode();
        }

        $items = $order->getAllVisibleItems();

        $orderId = $order->getIncrementId();
        $quoteId = $order->getQuoteId();


        $store = $order->getStore();
        $baseUrl = $store->getBaseUrl();
        // i want to use the url builder here, but that doenst work from admin, even if the store is supplied
        $returnUrl = $baseUrl . 'paynl/checkout/finish/';
        $exchangeUrl = $baseUrl . 'paynl/checkout/exchange/';

        $paymentOptionId = $this->getPaymentOptionId();

        $arrBillingAddress = $order->getBillingAddress();
        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();

            $enduser = array(
                'initials' => $arrBillingAddress['firstname'],
                'lastName' => $arrBillingAddress['lastname'],
                'phoneNumber' => $arrBillingAddress['telephone'],
                'emailAddress' => $arrBillingAddress['email'],
            );
            if (isset($additionalData['dob'])) {
                $enduser['dob'] = $additionalData['dob'];
            }

            if (isset($arrBillingAddress['company']) && !empty($arrBillingAddress['company'])) {
              $enduser['company']['name'] = $arrBillingAddress['company'];
              $enduser['company']['countryCode'] =  $arrBillingAddress['country_id'];
            }

            if (isset($kvknummer) && !empty($kvknummer)) {
              $enduser['company']['cocNumber'] = $kvknummer;
            }

            if (isset($arrBillingAddress['vat_id']) && !empty($arrBillingAddress['vat_id'])) {
              $enduser['company']['vatNumber'] = $arrBillingAddress['vat_id'];
            }

            $invoiceAddress = array(
                'initials' => $arrBillingAddress['firstname'],
                'lastName' => $arrBillingAddress['lastname']
            );

            $arrAddress = \Paynl\Helper::splitAddress($arrBillingAddress['street']);
            $invoiceAddress['streetName'] = $arrAddress[0];
            $invoiceAddress['houseNumber'] = $arrAddress[1];
            $invoiceAddress['zipCode'] = $arrBillingAddress['postcode'];
            $invoiceAddress['city'] = $arrBillingAddress['city'];
            $invoiceAddress['country'] = $arrBillingAddress['country_id'];

            if (isset($arrShippingAddress['vat_id']) && !empty($arrShippingAddress['vat_id'])) {
              $enduser['company']['vatNumber'] = $arrShippingAddress['vat_id'];
            }
        }

        $arrShippingAddress = $order->getShippingAddress();
        if ($arrShippingAddress) {
            $arrShippingAddress = $arrShippingAddress->toArray();

            $shippingAddress = array(
                'initials' => $arrShippingAddress['firstname'],
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
            'language' => $this->paynlConfig->getLanguage(),
            'bank' => $bankId,
            'expireDate' => $expireDate,
            'orderNumber' => $orderId,
            'description' => $orderId,
            'extra1' => $orderId,
            'extra2' => $quoteId,
            'extra3' => $order->getEntityId(),
            'exchangeUrl' => $exchangeUrl,
            'currency' => $currency,
            'object' => 'magento2 ' . $this->paynlConfig->getVersion(),
        );
        if (isset($shippingAddress)) {
            $data['address'] = $shippingAddress;
        }
        if (isset($invoiceAddress)) {
            $data['invoiceAddress'] = $invoiceAddress;
        }
        if (isset($enduser)) {
            $data['enduser'] = $enduser;
        }
        $arrProducts = array();
        foreach ($items as $item) {
            $arrItem = $item->toArray();
            if ($arrItem['price_incl_tax'] != null) {
                // taxamount is not valid, because on discount it returns the taxamount after discount
                $taxAmount = $arrItem['price_incl_tax'] - $arrItem['price'];
                $price = $arrItem['price_incl_tax'];

                if ($this->paynlConfig->isAlwaysBaseCurrency()) {
                    $taxAmount = $arrItem['base_price_incl_tax'] - $arrItem['base_price'];
                    $price = $arrItem['base_price_incl_tax'];
                }

                $product = array(
                    'id' => $arrItem['product_id'],
                    'name' => $arrItem['name'],
                    'price' => $price,
                    'qty' => $arrItem['qty_ordered'],
                    'tax' => $taxAmount,
                    'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE
                );

                # Product id's must be unique. Combinations of a "Configurable products" share the same product id.
                # Each combination of a "configurable product" can be represented by a "simple product".
                # The first and only child of the "configurable product" is the "simple product", or combination, chosen by the customer.
                # Grab it and replace the product id to guarantee product id uniqueness.
                if (isset($arrItem['product_type']) && $arrItem['product_type'] === Configurable::TYPE_CODE) {
                    $children = $item->getChildrenItems();
                    $child = array_shift($children);

                  if (!empty($child) && $child instanceof \Magento\Sales\Model\Order\Item && method_exists($child, 'getProductId')) {
                    $product['id'] = $child->getProductId();
                  }
                }

                $arrProducts[] = $product;
            }
        }

        //shipping
        $shippingCost = $order->getShippingInclTax();
        $shippingTax = $order->getShippingTaxAmount();

        if ($this->paynlConfig->isAlwaysBaseCurrency()) {
            $shippingCost = $order->getBaseShippingInclTax();
            $shippingTax = $order->getBaseShippingTaxAmount();
        }

        $shippingDescription = $order->getShippingDescription();

        if ($shippingCost != 0) {
            $arrProducts[] = array(
                'id' => 'shipping',
                'name' => $shippingDescription,
                'price' => $shippingCost,
                'qty' => 1,
                'tax' => $shippingTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            );
        }

        // kortingen
        $discount = $order->getDiscountAmount();
        $discountTax = $order->getDiscountTaxCompensationAmount() * -1;

        if ($this->paynlConfig->isAlwaysBaseCurrency()) {
            $discount = $order->getBaseDiscountAmount();
            $discountTax = $order->getBaseDiscountTaxCompensationAmount() * -1;
        }

        if ($this->paynlConfig->isSendDiscountTax() == 0) {
            $discountTax = 0;
        }

        $discountDescription = __('Discount');

        if ($discount != 0) {
            $arrProducts[] = array(
                'id' => 'discount',
                'name' => $discountDescription,
                'price' => $discount,
                'qty' => 1,
                'tax' => $discountTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_DISCOUNT
            );
        }

        $data['products'] = $arrProducts;

        if ($this->paynlConfig->isTestMode()) {
            $data['testmode'] = 1;
        }
        $ipAddress = $order->getRemoteIp();
        //The ip address field in magento is too short, if the ip is invalid, get the ip myself
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $ipAddress = \Paynl\Helper::getIp();
        }
        $data['ipaddress'] = $ipAddress;

       

        $transaction = \Paynl\Transaction::start($data);

        return $transaction;
    }

    public function getPaymentOptionId()
    {
        $paymentOptionId = $this->getConfigData('payment_option_id');

        if (empty($paymentOptionId)) $paymentOptionId = $this->getDefaultPaymentOptionId();

        return $paymentOptionId;
    }

    public function getCode()
    {        
        return $this->_code;
    }

    /**
     * @return int the default payment option id
     */
    abstract protected function getDefaultPaymentOptionId();
}
