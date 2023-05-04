<?php

namespace Paynl\Payment\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;

class PayPaymentCreate
{
    /**
     * @var integer
     */
    private $amount;

    /**
     * @var string
     */
    private $finishURL;

    /**
     * @var string
     */
    private $cocNumber;

    /**
     * @var string
     */
    private $vatNumber;

    /**
     * @var string[]
     */
    private $additionalData;

    /**
     * @var integer
     */
    private $paymentMethodId;

    /**
     * @var integer
     */
    private $testMode;

    /**
     * @var \Paynl\Payment\Model\Config
     */
    private $payConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PaymentMethod
     */
    private $methodInstance;

    /**
     * @var array
     */
    private $paymentData = [];

    /**
     * @var array
     */
    private $endUserData = [];

    /**
     * @var float|string|null
     */
    private $orderId;

    /**
     * @var Order
     */
    private $order;

    /**
     * @param Order $order
     * @param PaymentMethod $methodInstance
     * @throws \Exception
     */
    public function __construct(Order $order, PaymentMethod $methodInstance)
    {
        $this->methodInstance = $methodInstance;
        $this->payConfig = $methodInstance->paynlConfig;
        $this->testMode = $this->payConfig->isTestMode();
        $this->scopeConfig = $methodInstance->getScopeConfig();
        $this->order = $order;
        $this->orderId = $order->getIncrementId();
        $this->additionalData = $order->getPayment()->getAdditionalInformation();
        $this->setAmount($this->payConfig->isAlwaysBaseCurrency() ? $order->getBaseGrandTotal() : $order->getGrandTotal());
        $this->setCurrency($this->payConfig->isAlwaysBaseCurrency() ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode());
        $this->setCocNumber($this->additionalData['kvknummer'] ?? '');
        $this->setVatNumber($this->additionalData['vatnumber'] ?? '');
        $this->setIssuer($this->additionalData['payment_option'] ?? '');
        $this->setExpireData((int)($this->additionalData['valid_days'] ?? ''));
        $this->setFinishURL($this->additionalData['returnUrl'] ?? $order->getStore()->getBaseUrl() . 'paynl/checkout/finish/?entityid=' . $order->getEntityId());
        $this->setExchangeURL($this->additionalData['exchangeUrl'] ?? $order->getStore()->getBaseUrl() . 'paynl/checkout/exchange/');
        $this->setPaymentMethod($this->methodInstance->getPaymentOptionId());
    }

    /**
     * @return \Paynl\Result\Transaction\Start
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public function create()
    {
        $this->payConfig->configureSDK();

        return \Paynl\Transaction::start($this->getData());
    }

    /**
     * @param integer $paymentMethodId Setting the method, like iDEAL or banktransfer...
     * @return $this
     */
    public function setPaymentMethod($paymentMethodId)
    {
        $this->paymentMethodId = $paymentMethodId;
        return $this;
    }

    /**
     * @param string $cocNumber
     * @return $this
     */
    public function setCocNumber($cocNumber)
    {
        $this->cocNumber = $cocNumber;
        return $this;
    }

    /**
     * @param string $vatNumber
     * @return $this
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
        return $this;
    }

    /**
     * @param string $issuer
     * @return $this
     */
    public function setIssuer($issuer)
    {
        $this->paymentData['bank'] = $issuer ?? null;
        return $this;
    }

    /**
     * @param string $finishUrl
     * @return $this
     */
    public function setFinishURL(string $finishUrl)
    {
        $this->paymentData['returnURL'] = $finishUrl;
        return $this;
    }

    /**
     * @param string $exchangeURL
     * @return $this
     */
    public function setExchangeURL(string $exchangeURL)
    {
        $this->paymentData['exchangeURL'] = $exchangeURL;
        return $this;
    }

    /**
     * @param integer $valid_days
     * @return $this
     * @throws \Exception
     */
    public function setExpireData(int $valid_days)
    {
        if (!empty($valid_days) && is_numeric($valid_days)) {
            $this->paymentData['expireDate'] = new \DateTime('+' . $valid_days . ' days');
        }
        return $this;
    }

    /**
     * @param integer $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->paymentData['amount'] = $amount;
        return $this;
    }

    /**
     * @param string $currency Currency code
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->paymentData['currency'] = $currency;
        return $this;
    }

    /**
     * @return float|string|null
     */
    private function getDescription()
    {
        $prefix = $this->scopeConfig->getValue('payment/paynl/order_description_prefix', 'store');
        return !empty($prefix) ? $prefix . $this->orderId : $this->orderId;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $shippingAddress = $this->getShippingAddress();
        $endUserData = $this->getEnduserData();
        $invoiceAddress = $this->getInvoiceAddress();
        $productData = $this->getProductData();

        $data = [
            'amount' => $this->paymentData['amount'],
            'returnUrl' => $this->paymentData['returnURL'],
            'paymentMethod' => $this->paymentMethodId,
            'language' => $this->payConfig->getLanguage(),
            'bank' => $this->paymentData['bank'] ?? '',
            'orderNumber' => $this->orderId,
            'description' => $this->getDescription(),
            'extra1' => $this->orderId,
            'extra2' => $this->order->getQuoteId(),
            'extra3' => $this->order->getEntityId(),
            'transferData' => $this->methodInstance->getTransferData(),
            'exchangeUrl' => $this->paymentData['exchangeURL'],
            'currency' => $this->paymentData['currency'],
            'object' => $this->methodInstance->getVersion(),
        ];

        if (!empty($shippingAddress)) {
            $data['address'] = $shippingAddress;
        }
        if (!empty($invoiceAddress)) {
            $data['invoiceAddress'] = $invoiceAddress;
        }
        if (!empty($endUserData)) {
            $data['enduser'] = $endUserData;
        }
        if (!empty($productData)) {
            $data['products'] = $productData;
        }

        $data['testmode'] = $this->testMode;
        $data['ipaddress'] = $this->getIpAddress();

        return $data;
    }

    /**
     * @return float|string|null
     */
    private function getIpAddress()
    {
        $ipAddress = $this->order->getRemoteIp();

        # The ip address field in magento is too short, if the ip is invalid or ip is localhost get the ip myself
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP) || $ipAddress == '127.0.0.1') {
            $ipAddress = \Paynl\Helper::getIp();
        }
        return $ipAddress;
    }

    /**
     * @return boolean
     */
    private function useBillingAddressInstorePickup()
    {
        return $this->scopeConfig->getValue('payment/' . $this->methodInstance->getCode() . '/useBillingAddressInstorePickup', 'store') == 1;
    }

    /**
     * @return array
     */
    private function getShippingAddress()
    {
        $shippingAddress = null;
        $orderShippingAddress = $this->order->getShippingAddress();

        if (!empty($orderShippingAddress)) {
            $arrShippingAddress = $orderShippingAddress->toArray();

            if ($this->useBillingAddressInstorePickup() && class_exists('InStorePickup')) {
                if ($this->order->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
                    $arrBillingAddress = $this->order->getBillingAddress();
                    if (!empty($arrBillingAddress)) {
                        $arrShippingAddress = $arrBillingAddress->toArray();
                    }
                }
            }

            $shippingAddress = [
                'initials' => $arrShippingAddress['firstname'],
                'lastName' => $arrShippingAddress['lastname'],
            ];
            $arrAddress2 = \Paynl\Helper::splitAddress($arrShippingAddress['street']);
            $shippingAddress['streetName'] = $arrAddress2[0] ?? '';
            $shippingAddress['houseNumber'] = $arrAddress2[1] ?? '';
            $shippingAddress['zipCode'] = $arrShippingAddress['postcode'];
            $shippingAddress['city'] = $arrShippingAddress['city'];
            $shippingAddress['country'] = $arrShippingAddress['country_id'];
        }

        return $shippingAddress;
    }

    /**
     * @return array
     */
    private function getEnduserData()
    {
        $arrBillingAddress = $this->order->getBillingAddress();
        $enduser = [];

        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();
            $enduser = [
                'initials' => $arrBillingAddress['firstname'],
                'lastName' => $arrBillingAddress['lastname'],
                'phoneNumber' => payHelper::validatePhoneNumber($arrBillingAddress['telephone']),
                'emailAddress' => $arrBillingAddress['email'],
            ];
            if (isset($this->additionalData['dob'])) {
                $enduser['dob'] = $this->additionalData['dob'];
            }
            if (isset($this->additionalData['gender'])) {
                $enduser['gender'] = $this->additionalData['gender'];
            }
            $enduser['gender'] = payHelper::genderConversion((empty($enduser['gender'])) ? $this->order->getCustomerGender($this->order) : $enduser['gender']);
            if (!empty($arrBillingAddress['company'])) {
                $enduser['company']['name'] = $arrBillingAddress['company'];
            }
            if (!empty($arrBillingAddress['country_id'])) {
                $enduser['company']['countryCode'] = $arrBillingAddress['country_id'];
            }
            if (!empty($this->cocNumber)) {
                $enduser['company']['cocNumber'] = $this->cocNumber;
            }
            if (!empty($arrBillingAddress['vat_id'])) {
                $enduser['company']['vatNumber'] = $arrBillingAddress['vat_id'];
            } elseif (!empty($this->vatNumber)) {
                $enduser['company']['vatNumber'] = $this->vatNumber;
            }
        }

        return $enduser;
    }

    /**
     * @return array
     */
    private function getInvoiceAddress()
    {
        $arrBillingAddress = $this->order->getBillingAddress();
        $invoiceAddress = null;

        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();

            $invoiceAddress = [
                'initials' => $arrBillingAddress['firstname'] ?? '',
                'lastName' => $arrBillingAddress['lastname'] ?? '',
            ];

            $arrAddress = \Paynl\Helper::splitAddress($arrBillingAddress['street']);
            $invoiceAddress['streetName'] = $arrAddress[0];
            $invoiceAddress['houseNumber'] = $arrAddress[1];
            $invoiceAddress['zipCode'] = $arrBillingAddress['postcode'];
            $invoiceAddress['city'] = $arrBillingAddress['city'];
            $invoiceAddress['country'] = $arrBillingAddress['country_id'];
        }

        return $invoiceAddress;
    }

    /**
     * @return array
     */
    private function getProductData()
    {
        $arrProducts = [];
        $arrWeeeTax = [];

        foreach ($this->order->getAllVisibleItems() as $item) {
            $arrItem = $item->toArray();
            if ($arrItem['price_incl_tax'] != null) {
                // taxamount is not valid, because on discount it returns the taxamount after discount
                $taxAmount = $arrItem['price_incl_tax'] - $arrItem['price'];
                $price = $arrItem['price_incl_tax'];

                if ($this->payConfig->isAlwaysBaseCurrency()) {
                    $taxAmount = $arrItem['base_price_incl_tax'] - $arrItem['base_price'];
                    $price = $arrItem['base_price_incl_tax'];
                }

                $productId = $arrItem['product_id'];
                if ($this->payConfig->useSkuId()) {
                    $productId = $arrItem['sku'];
                }

                $product = [
                    'id' => $productId,
                    'name' => $arrItem['name'],
                    'price' => $price,
                    'qty' => $arrItem['qty_ordered'],
                    'tax' => $taxAmount,
                    'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE,
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
                        if ($this->payConfig->useSkuId() && method_exists($child, 'getSku')) {
                            $productIdChild = $child->getSku();
                        }
                        $product['id'] = $productIdChild;
                    }
                }

                $arrProducts[] = $product;

                # Check for Weee-tax
                if (!empty($arrItem['weee_tax_applied'])) {
                    $weeeArr = json_decode($arrItem['weee_tax_applied']);
                    if (is_array($weeeArr)) {
                        foreach ($weeeArr as $weeeItem) {
                            if (!empty($weeeItem) && is_object($weeeItem)) {
                                $weeeTitle = $weeeItem->title;
                                $weeePrice = $weeeItem->row_amount_incl_tax;
                                $weeeTaxAmount = $weeeItem->row_amount_incl_tax - $weeeItem->row_amount;

                                if ($this->payConfig->isAlwaysBaseCurrency()) {
                                    $weeePrice = $weeeItem->base_row_amount_incl_tax;
                                    $weeeTaxAmount = $weeeItem->base_row_amount_incl_tax - $weeeItem->base_row_amount;
                                }

                                if (isset($arrWeeeTax[$weeeTitle])) {
                                    $arrWeeeTax[$weeeTitle]['price'] += $weeePrice;
                                    $arrWeeeTax[$weeeTitle]['tax'] += $weeeTaxAmount;
                                } else {
                                    $arrWeeeTax[$weeeTitle] = array(
                                        'id' => 'weee',
                                        'name' => $weeeTitle,
                                        'price' => $weeePrice,
                                        'tax' => $weeeTaxAmount,
                                        'qty' => 1,
                                        'type' => \Paynl\Transaction::PRODUCT_TYPE_HANDLING,
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        # Shipping
        $shippingCost = $this->order->getShippingInclTax();
        $shippingTax = $this->order->getShippingTaxAmount();

        if ($this->payConfig->isAlwaysBaseCurrency()) {
            $shippingCost = $this->order->getBaseShippingInclTax();
            $shippingTax = $this->order->getBaseShippingTaxAmount();
        }

        $shippingDescription = $this->order->getShippingDescription();

        if ($shippingCost != 0) {
            $arrProducts[] = [
                'id' => 'shipping',
                'name' => empty($shippingDescription) ? 'Shipping' : $shippingDescription,
                'price' => $shippingCost,
                'qty' => 1,
                'tax' => $shippingTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING,
            ];
        }

        // Gift Wrapping
        $gwCost = $this->order->getGwPriceInclTax();
        $gwTax = $this->order->getGwTaxAmount();

        if ($this->payConfig->isAlwaysBaseCurrency()) {
            $gwCost = $this->order->getGwBasePriceInclTax();
            $gwTax = $this->order->getGwBaseTaxAmount();
        }

        if ($gwCost != 0) {
            $arrProducts[] = [
                'id' => $this->order->getGwId(),
                'name' => 'Gift Wrapping',
                'price' => $gwCost,
                'qty' => 1,
                'tax' => $gwTax,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_HANDLING,
            ];
        }

        // kortingen
        $discount = $this->order->getDiscountAmount();
        $discountTax = $this->order->getDiscountTaxCompensationAmount() * -1;

        if ($this->payConfig->isAlwaysBaseCurrency()) {
            $discount = $this->order->getBaseDiscountAmount();
            $discountTax = $this->order->getBaseDiscountTaxCompensationAmount() * -1;
        }

        if ($this->payConfig->isSendDiscountTax() == 0) {
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
                'type' => \Paynl\Transaction::PRODUCT_TYPE_DISCOUNT,
            ];
        }

        if (!empty($arrWeeeTax)) {
            $arrProducts = array_merge($arrProducts, $arrWeeeTax);
        }

        return $arrProducts;
    }
}
