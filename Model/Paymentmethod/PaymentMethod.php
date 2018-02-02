<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

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

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
	/**
	 * @var Config
	 */
    protected $paynlConfig;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    public function __construct(
    	\Magento\Framework\Model\Context $context,
	    \Magento\Framework\Registry $registry,
	    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
	    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
	    \Magento\Payment\Helper\Data $paymentData,
	    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
	    \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
	    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
	    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
	    array $data = []
    ) {
	    parent::__construct(
	    	$context, $registry, $extensionFactory, $customAttributeFactory,
		    $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data );

	    $this->orderRepository = $orderRepository;
	    $this->orderConfig = $orderConfig;
	    $this->paynlConfig = new Config($this->_scopeConfig);
    }

    protected function getState($status){
        $validStates = [
            Order::STATE_NEW,
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_HOLDED
        ];

        foreach($validStates as $state){
            $statusses = $this->orderConfig->getStateStatuses($state, false);
            if(in_array($status, $statusses)) return $state;
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
    public function getBanks(){
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
        $order   = $payment->getOrder();

	    if($sendEmail == 'after_payment') {
	    	//prevent sending the order confirmation
		    $order->setCanSendNewEmailFlag( false );
	    }

        $this->orderRepository->save($order);

	    return parent::initialize($paymentAction, $stateObject);
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();

        \Paynl\Transaction::refund($transactionId, $amount);

        return true;
    }
    public function startTransaction(Order $order){
        $transaction = $this->doStartTransaction($order);

        $holded = $this->_scopeConfig->getValue('payment/' . $this->_code . '/holded', 'store');
        if($holded){
            $order->hold();
        }
        $this->orderRepository->save($order);

        return $transaction->getRedirectUrl();
    }
    protected function doStartTransaction(Order $order)
    {
	    $this->paynlConfig->configureSDK();
        $additionalData = $order->getPayment()->getAdditionalInformation();
        $bankId = null;
        $expireDate = null;
        if(isset($additionalData['bank_id']) && is_numeric($additionalData['bank_id'])){
            $bankId = $additionalData['bank_id'];
        }
        if(isset($additionalData['valid_days']) && is_numeric($additionalData['valid_days'])){
	        $expireDate = new \DateTime('+'.$additionalData['valid_days'].' days');
        }

        if($this->paynlConfig->isAlwaysBaseCurrency()){
            $total = $order->getBaseGrandTotal();
            $currency = $order->getBaseCurrencyCode();
        } else{
            $total = $order->getGrandTotal();
            $currency = $order->getOrderCurrencyCode();
        }

        $items = $order->getAllVisibleItems();

        $orderId = $order->getIncrementId();
        $quoteId = $order->getQuoteId();



		$store = $order->getStore();
		$baseUrl = $store->getBaseUrl();
		// i want to use the url builder here, but that doenst work from admin, even if the store is supplied
	    $returnUrl = $baseUrl.'paynl/checkout/finish/';
	    $exchangeUrl = $baseUrl.'paynl/checkout/exchange/';

        $paymentOptionId = $this->getPaymentOptionId();

        $arrBillingAddress = $order->getBillingAddress();
        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();

            // Use default initials
            $strBillingFirstName = substr($arrBillingAddress['firstname'], 0, 1);

            // Use full first name for Klarna
            if($paymentOptionId == $this->paynlConfig->getPaymentOptionId('paynl_payment_klarna'))
            {
                $strBillingFirstName = $arrBillingAddress['firstname'];
            }

            $enduser = array(
                'initials' => $strBillingFirstName,
                'lastName' => $arrBillingAddress['lastname'],
                'phoneNumber' => $arrBillingAddress['telephone'],
                'emailAddress' => $arrBillingAddress['email'],
            );

            $invoiceAddress = array(
                'initials' => $strBillingFirstName,
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

            // Use default initials
            $strShippingFirstName = substr($arrShippingAddress['firstname'], 0, 1);

            // Use full first name for Klarna
            if($paymentOptionId == $this->paynlConfig->getPaymentOptionId('paynl_payment_klarna'))
            {
                $strShippingFirstName = $arrShippingAddress['firstname'];
            }

            $shippingAddress = array(
                'initials' => $strShippingFirstName,
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
            'description' => $orderId,
            'extra1' => $orderId,
            'extra2' => $quoteId,
            'extra3' => $order->getEntityId(),
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
                $price = $arrItem['price_incl_tax'];

                if($this->paynlConfig->isAlwaysBaseCurrency()){
                    $taxAmount = $arrItem['base_price_incl_tax'] - $arrItem['base_price'];
                    $price = $arrItem['base_price_incl_tax'];
                }
                $product = array(
                    'id' => $arrItem['product_id'],
                    'name' => $arrItem['name'],
                    'price' => $price,
                    'qty' => $arrItem['qty_ordered'],
                    'tax' => $taxAmount,
                );
                $arrProducts[] = $product;
            }
        }

        //shipping
        $shippingCost = $order->getShippingInclTax();
        $shippingTax = $order->getShippingTaxAmount();

        if($this->paynlConfig->isAlwaysBaseCurrency()){
            $shippingCost = $order->getBaseShippingInclTax();
            $shippingTax = $order->getBaseShippingTaxAmount();
        }

        $shippingDescription = $order->getShippingDescription();

        if($shippingCost != 0) {
            $arrProducts[] = array(
                'id' => 'shipping',
                'name' => $shippingDescription,
                'price' => $shippingCost,
                'qty' => 1,
                'tax' => $shippingTax
            );
        }

        // kortingen
        $discount = $order->getDiscountAmount();
        $discountTax = $order->getDiscountTaxCompensationAmount() * -1;

        if($this->paynlConfig->isAlwaysBaseCurrency()){
            $discount = $order->getBaseDiscountAmount();
            $discountTax = $order->getBaseDiscountTaxCompensationAmount() * -1;
        }

        $discountDescription = $order->getDiscountDescription();

        if ($discount != 0) {
            $arrProducts[] = array(
                'id' => 'discount',
                'name' => $discountDescription,
                'price' => $discount,
                'qty' => 1,
                'tax' => $discountTax
            );
        }

        $data['products'] = $arrProducts;

        if ($this->paynlConfig->isTestMode()) {
            $data['testmode'] = 1;
        }
        $ipAddress = $order->getRemoteIp();
        //The ip address field in magento is too short, if the ip is invalid, get the ip myself
        if(!filter_var($ipAddress, FILTER_VALIDATE_IP)){
        	$ipAddress = \Paynl\Helper::getIp();
        }
        $data['ipaddress'] = $ipAddress;

        $transaction = \Paynl\Transaction::start($data);

        return $transaction;
    }

    public function getPaymentOptionId()
    {
        return $this->getConfigData('payment_option_id');
    }
}