<?php

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
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Paynl\Transaction;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;

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

    protected $helper;

    /**
     * @var Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $methodLogger,
        \Magento\Sales\Model\Order\Config $orderConfig,
        OrderRepository $orderRepository,
        Config $paynlConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $methodLogger,
            $resource,
            $resourceCollection,
            $data
        );

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->messageManager = $objectManager->get(\Magento\Framework\Message\ManagerInterface::class);
        $this->helper = $objectManager->create(\Paynl\Payment\Helper\PayHelper::class);
        $this->paynlConfig = $paynlConfig;
        $this->orderRepository = $orderRepository;
        $this->orderConfig = $orderConfig;
        $this->storeManager = $objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->cookieManager = $objectManager->create('\Magento\Framework\Stdlib\CookieManagerInterface');
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
            if (in_array($status, $statusses)) {
                return $state;
            }
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
        return $this->getConfigData('instructions');
    }

    public function getPaymentOptions()
    {
        return [];
    }

    public function showPaymentOptions()
    {
        return false;
    }

    public function hidePaymentOptions()
    {
        return 0;
    }

    public function getKVK()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showkvk', 'store');
    }

    public function getVAT()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showvat', 'store');
    }

    public function getDOB()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showdob', 'store');
    }

    public function getDisallowedShippingMethods()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/disallowedshipping', 'store');
    }

    public function getCompany()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showforcompany', 'store');
    }

    public function getCustomerGroup()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showforgroup', 'store');
    }

    /**
     * @return bool
     */
    public function useBillingAddressInstorePickup()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/useBillingAddressInstorePickup', 'store') == 1;
    }

    public function isCurrentIpValid()
    {
        return true;
    }

    public function isCurrentAgentValid()
    {
        return true;
    }

    public function isDefaultPaymentOption()
    {
        $default_payment_option = $this->paynlConfig->getDefaultPaymentOption();
        return ($default_payment_option == $this->_code);
    }

    public function getTransferData()
    {
        $transferData = array();

        # Get Magento's Google Analytics cookie
        if ($this->paynlConfig->sendEcommerceAnalytics()) {
            $_gaCookie = $this->cookieManager->getCookie('_ga');
            if (!empty($_gaCookie)) {
                $_gaSplit = explode('.', $_gaCookie);
                if (isset($_gaSplit[2]) && isset($_gaSplit[3])) {
                    $transferData['gaClientId'] = $_gaSplit[2] . '.' . $_gaSplit[3];
                }
            } else {
                payHelper::logDebug('Cookie empty for GA', array());
            }
        } else {
            payHelper::logDebug('GA to PAY. not enabled.', array());
        }

        return $transferData;
    }

    public function genderConversion($gender)
    {
        switch ($gender) {
            case '1':
                $gender = 'M';
                break;
            case '2':
                $gender = 'F';
                break;
            default:
                $gender = null;
                break;
        }
        return $gender;
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
        $order = $payment->getOrder();
        $this->paynlConfig->setStore($order->getStore());
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();
        $transactionId = str_replace('-capture', '', $transactionId);

        try {
            Transaction::refund($transactionId, $amount);
        } catch (\Exception $e) {

            $docsLink = 'https://docs.pay.nl/plugins#magento2-errordefinitions';

            $message = strtolower($e->getMessage());
            if (substr($message, 0, 19) == '403 - access denied') {
                $message = 'PAY. could not authorize this refund. Errorcode: PAY-MAGENTO2-001. See for more information ' . $docsLink;
            } else {
                $message = 'PAY. could not process this refund (' . $message . '). Errorcode: PAY-MAGENTO2-002. Transaction: '.$transactionId.'. More info: ' . $docsLink;
            }

            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }

        return $this;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        $payment->setAdditionalInformation('manual_capture', 'true');
        $order = $payment->getOrder();
        $order->save();
        $this->paynlConfig->setStore($order->getStore());
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();

        Transaction::capture($transactionId);

        return $this;
    }

    public function void(InfoInterface $payment)
    {
        $order = $payment->getOrder();
        $this->paynlConfig->setStore($order->getStore());
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();

        Transaction::void($transactionId);

        return $this;
    }

    public function startTransaction(Order $order, $graphqlVersion = null)
    {
        $transaction = $this->doStartTransaction($order, $graphqlVersion);
        $order->getPayment()->setAdditionalInformation('transactionId', $transaction->getTransactionId());
        $this->paynlConfig->setStore($order->getStore());

        $holded = $this->_scopeConfig->getValue('payment/' . $this->_code . '/holded', 'store');
        if ($holded) {
            $order->hold();
        }
        $this->orderRepository->save($order);

        return $transaction->getRedirectUrl();
    }

    protected function doStartTransaction(Order $order, $graphqlVersion = null)
    {
        $this->paynlConfig->setStore($order->getStore());
        $this->paynlConfig->configureSDK();
        $additionalData = $order->getPayment()->getAdditionalInformation();
        $paymentOption = null;
        $expireDate = null;

        if (isset($additionalData['kvknummer']) && is_numeric($additionalData['kvknummer'])) {
            $kvknummer = $additionalData['kvknummer'];
        }
        if (isset($additionalData['vatnumber'])) {
            $vatnumber = $additionalData['vatnumber'];
        }
        if (isset($additionalData['payment_option']) && is_numeric($additionalData['payment_option'])) {
            $paymentOption = $additionalData['payment_option'];
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

        $returnUrl = $additionalData['returnUrl'] ?? $baseUrl . 'paynl/checkout/finish/?entityid=' . $order->getEntityId();
        $exchangeUrl = $additionalData['exchangeUrl'] ?? $baseUrl . 'paynl/checkout/exchange/';

        $paymentOptionId = $this->getPaymentOptionId();

        $arrBillingAddress = $order->getBillingAddress();
        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();

            $enduser = [
                'initials' => $arrBillingAddress['firstname'],
                'lastName' => $arrBillingAddress['lastname'],
                'phoneNumber' => $arrBillingAddress['telephone'],
                'emailAddress' => $arrBillingAddress['email'],
            ];

            if (isset($additionalData['dob'])) {
                $enduser['dob'] = $additionalData['dob'];
            }

            if (isset($additionalData['gender'])) {
                $enduser['gender'] = $additionalData['gender'];
            }
            $enduser['gender'] = $this->genderConversion((empty($enduser['gender'])) ? $order->getCustomerGender($order) : $enduser['gender']);

            if (!empty($arrBillingAddress['company'])) {
                $enduser['company']['name'] = $arrBillingAddress['company'];
            }

            if (!empty($arrBillingAddress['country_id'])) {
                $enduser['company']['countryCode'] =  $arrBillingAddress['country_id'];
            }

            if (!empty($kvknummer)) {
                $enduser['company']['cocNumber'] = $kvknummer;
            }

            if (!empty($arrBillingAddress['vat_id'])) {
                $enduser['company']['vatNumber'] = $arrBillingAddress['vat_id'];
            } elseif (!empty($vatnumber)) {
                $enduser['company']['vatNumber'] = $vatnumber;
            }

            $invoiceAddress = [
                'initials' => $arrBillingAddress['firstname'],
                'lastName' => $arrBillingAddress['lastname']
            ];

            $arrAddress = \Paynl\Helper::splitAddress($arrBillingAddress['street']);
            $invoiceAddress['streetName'] = $arrAddress[0];
            $invoiceAddress['houseNumber'] = $arrAddress[1];
            $invoiceAddress['zipCode'] = $arrBillingAddress['postcode'];
            $invoiceAddress['city'] = $arrBillingAddress['city'];
            $invoiceAddress['country'] = $arrBillingAddress['country_id'];

            if (!empty($arrShippingAddress['vat_id'])) {
                $enduser['company']['vatNumber'] = $arrShippingAddress['vat_id'];
            }
        }

        $arrShippingAddress = $order->getShippingAddress();
        if (!empty($arrShippingAddress)) {
            $arrShippingAddress = $arrShippingAddress->toArray();

            if ($this->useBillingAddressInstorePickup() && class_exists('InStorePickup')) {
                if ($order->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
                    $arrBillingAddress = $order->getBillingAddress();
                    if (!empty($arrBillingAddress)) {
                        $arrShippingAddress = $arrBillingAddress->toArray();
                    }
                }
            }

            $shippingAddress = [
                'initials' => $arrShippingAddress['firstname'],
                'lastName' => $arrShippingAddress['lastname']
            ];
            $arrAddress2 = \Paynl\Helper::splitAddress($arrShippingAddress['street']);
            $shippingAddress['streetName'] = $arrAddress2[0];
            $shippingAddress['houseNumber'] = $arrAddress2[1];
            $shippingAddress['zipCode'] = $arrShippingAddress['postcode'];
            $shippingAddress['city'] = $arrShippingAddress['city'];
            $shippingAddress['country'] = $arrShippingAddress['country_id'];

        }

        $prefix = $this->_scopeConfig->getValue('payment/paynl/order_description_prefix', 'store');
        $description = !empty($prefix) ? $prefix . $orderId : $orderId;

        $data = [
            'amount' => $total,
            'returnUrl' => $returnUrl,
            'paymentMethod' => $paymentOptionId,
            'language' => $this->paynlConfig->getLanguage(),
            'bank' => $paymentOption,
            'expireDate' => $expireDate,
            'orderNumber' => $orderId,
            'description' => $description,
            'extra1' => $orderId,
            'extra2' => $quoteId,
            'extra3' => $order->getEntityId(),
            'transferData' => $this->getTransferData(),
            'exchangeUrl' => $exchangeUrl,
            'currency' => $currency,
            'object' => substr('magento2 ' . $this->paynlConfig->getVersion() . ' | ' . (!empty($graphqlVersion) ? 'graphQL ' . $graphqlVersion . ' | ' : '') . $this->paynlConfig->getMagentoVersion() . ' | ' . $this->paynlConfig->getPHPVersion(), 0, 64),
        ];
        if (isset($shippingAddress)) {
            $data['address'] = $shippingAddress;
        }
        if (isset($invoiceAddress)) {
            $data['invoiceAddress'] = $invoiceAddress;
        }
        if (isset($enduser)) {
            $data['enduser'] = $enduser;
        }
        $arrProducts = [];
        $arrWEEETax = [];
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

                $productId = $arrItem['product_id'];
                if ($this->paynlConfig->useSkuId()) {
                    $productId = $arrItem['sku'];
                }

                $product = [
                    'id' => $productId,
                    'name' => $arrItem['name'],
                    'price' => $price,
                    'qty' => $arrItem['qty_ordered'],
                    'tax' => $taxAmount,
                    'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE
                ];

                # Product id's must be unique. Combinations of a "Configurable products" share the same product id.
                # Each combination of a "configurable product" can be represented by a "simple product".
                # The first and only child of the "configurable product" is the "simple product", or combination, chosen by the customer.
                # Grab it and replace the product id to guarantee product id uniqueness.
                if (isset($arrItem['product_type']) && $arrItem['product_type'] === Configurable::TYPE_CODE) {
                    $children = $item->getChildrenItems();
                    $child = array_shift($children);

                    if (!empty($child) && $child instanceof \Magento\Sales\Model\Order\Item && method_exists($child, 'getProductId')) {
                        $productIdChild = $child->getProductId();
                        if ($this->paynlConfig->useSkuId() && method_exists($child, 'getSku')) {
                            $productIdChild = $child->getSku();
                        }
                        $product['id'] = $productIdChild;
                    }
                }

                $arrProducts[] = $product;

                # WEEE
                if (!empty($arrItem['weee_tax_applied'])) {
                    $weeeArr = json_decode($arrItem['weee_tax_applied']);
                    if (is_array($weeeArr)) {
                        foreach ($weeeArr as $weee) {
                            if (!empty($weee) && is_object($weee)) {

                                $weee_title = $weee->title;
                                $weee_price = $weee->row_amount_incl_tax;
                                $weee_taxAmount = $weee->row_amount_incl_tax - $weee->row_amount;

                                if ($this->paynlConfig->isAlwaysBaseCurrency()) {
                                    $weee_price = $weee->base_row_amount_incl_tax;
                                    $weee_taxAmount = $weee->base_row_amount_incl_tax - $weee->base_row_amount;
                                }

                                if (isset($arrWEEETax[$weee_title])) {
                                    $arrWEEETax[$weee_title]['price'] += $weee_price;
                                    $arrWEEETax[$weee_title]['tax'] += $weee_taxAmount;
                                } else {
                                    $arrWEEETax[$weee_title] = array(
                                        'id' => 'weee',
                                        'name' => $weee_title,
                                        'price' => $weee_price,
                                        'tax' => $weee_taxAmount,
                                        'qty' => 1,
                                        'type' => \Paynl\Transaction::PRODUCT_TYPE_HANDLING
                                    );
                                }
                            }
                        }
                    }
                }
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
            $arrProducts[] = [
                'id' => 'shipping',
                'name' => $shippingDescription,
                'price' => $shippingCost,
                'qty' => 1,
                'tax' => $shippingTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            ];
        }

        // Gift Wrapping
        $gwCost = $order->getGwPriceInclTax();
        $gwTax = $order->getGwTaxAmount();

        if ($this->paynlConfig->isAlwaysBaseCurrency()) {
            $gwCost = $order->getGwBasePriceInclTax();
            $gwTax = $order->getGwBaseTaxAmount();
        }

        if ($gwCost != 0) {
            $arrProducts[] = [
                'id' => $order->getGwId(),
                'name' => 'Gift Wrapping',
                'price' => $gwCost,
                'qty' => 1,
                'tax' => $gwTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_HANDLING
            ];
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
            $arrProducts[] = [
                'id' => 'discount',
                'name' => $discountDescription,
                'price' => $discount,
                'qty' => 1,
                'tax' => $discountTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_DISCOUNT
            ];
        }

        // WEEE
        if (!empty($arrWEEETax)) {
            $arrProducts = array_merge($arrProducts, $arrWEEETax);
        }

        $data['products'] = $arrProducts;

        if ($this->paynlConfig->isTestMode()) {
            $data['testmode'] = 1;
        }
        $ipAddress = $order->getRemoteIp();
        //The ip address field in magento is too short, if the ip is invalid or ip is localhost get the ip myself
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP) || $ipAddress == '127.0.0.1') {
            $ipAddress = \Paynl\Helper::getIp();
        }
        $data['ipaddress'] = $ipAddress;

        $transaction = \Paynl\Transaction::start($data);

        return $transaction;
    }

    public function getPaymentOptionId()
    {
        $paymentOptionId = $this->getConfigData('payment_option_id');

        if (empty($paymentOptionId)) {
            $paymentOptionId = $this->getDefaultPaymentOptionId();
        }

        return $paymentOptionId;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            if (isset($data['kvknummer'])) {
                $this->getInfoInstance()->setAdditionalInformation('kvknummer', $data['kvknummer']);
            }
            if (isset($data['vatnumber'])) {
                $this->getInfoInstance()->setAdditionalInformation('vatnumber', $data['vatnumber']);
            }
            if (isset($data['dob'])) {
                $this->getInfoInstance()->setAdditionalInformation('dob', $data['dob']);
            }
        } elseif ($data instanceof \Magento\Framework\DataObject) {

            $additional_data = $data->getAdditionalData();

            if (isset($additional_data['kvknummer'])) {
                $this->getInfoInstance()->setAdditionalInformation('kvknummer', $additional_data['kvknummer']);
            }

            if (isset($additional_data['vatnumber'])) {
                $this->getInfoInstance()->setAdditionalInformation('vatnumber', $additional_data['vatnumber']);
            }

            if (isset($additional_data['billink_agree'])) {
                $this->getInfoInstance()->setAdditionalInformation('billink_agree', $additional_data['billink_agree']);
            }

            if (isset($additional_data['dob'])) {
                $this->getInfoInstance()->setAdditionalInformation('dob', $additional_data['dob']);
            }
        }
        return $this;
    }

    /**
     * @return int the default payment option id
     */
    abstract protected function getDefaultPaymentOptionId();
}
