<?php

namespace Paynl\Payment\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use Magento\Store\Model\StoreManagerInterface;
use PayNL\Sdk\Model\Pay\PayOrder;
use PayNL\Sdk\Model\Product;
use PayNL\Sdk\Model\Request\OrderCreateRequest;
use PayNL\Sdk\Exception\PayException;
use PayNL\Sdk\Config\Config;
use PayNL\Sdk\Model\Products;
use PayNL\Sdk\Model\Product as PayProduct;

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
    private $companyField;

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
    protected $paymentMethodId;

    /**
     * @var integer
     */
    protected $testMode;

    /**
     * @var \Paynl\Payment\Model\Config
     */
    protected $payConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PaymentMethod
     */
    protected $methodInstance;

    /**
     * @var array
     */
    protected $paymentData = [];

    /**
     * @var array
     */
    private $endUserData = [];

    /**
     * @var float|string|null
     */
    protected $orderId;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var OrderCreateRequest
     */
    protected OrderCreateRequest $request;

    /**
     * @param null|Order $order
     * @param PaymentMethod $methodInstance
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function __construct($order, PaymentMethod $methodInstance)
    {
        $this->request = new OrderCreateRequest();

        $this->methodInstance = $methodInstance;
        $this->payConfig = $methodInstance->paynlConfig;
        $this->testMode = $this->payConfig->isTestMode();
        $this->scopeConfig = $methodInstance->getScopeConfig();
        $finishUrl = $exchangeUrl = '';

        if ($order instanceof Order) {
            $this->order = $order;
            $this->orderId = $order->getIncrementId();
            $this->additionalData = $order->getPayment()->getAdditionalInformation();

            $this->setAmount($this->payConfig->isAlwaysBaseCurrency() ? $order->getBaseGrandTotal() : $order->getGrandTotal());
            $this->setCurrency($this->payConfig->isAlwaysBaseCurrency() ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode());

            $finishUrl = $order->getStore()->getBaseUrl() . 'paynl/checkout/finish/?entityid=' . $order->getEntityId();
            $exchangeUrl = $order->getStore()->getBaseUrl() . 'paynl/checkout/exchange/';

            $this->payConfig->setStore($order->getStore());
        }

        $this->setCompanyField($this->additionalData['companyfield'] ?? '');
        $this->setCocNumber($this->additionalData['cocnumber'] ?? '');
        $this->setVatNumber($this->additionalData['vatnumber'] ?? '');

        if ($this->methodInstance->getPaymentOptionId() == 1927) {
            $this->request->setTerminal($this->additionalData['payment_option'] ?? '');
        }

        $validDays = $this->additionalData['valid_days'] ?? null;
        if (!empty($validDays)) {
            $this->setExpireData((int) $validDays, 'days');
        } elseif ($this->payConfig->getExpireTime() > 0) {
            $this->setExpireData($this->payConfig->getExpireTime(), 'minutes');
        }

        $this->setIssuer($this->additionalData['payment_option'] ?? '');
        $this->setFinishURL($this->additionalData['returnUrl'] ?? $finishUrl);
        $this->setExchangeURL($this->additionalData['exchangeUrl'] ?? $exchangeUrl);
        $this->setPaymentMethod($this->methodInstance->getPaymentOptionId());

        return $this;
    }

    /**
     * @return void
     */
    private function getStats()
    {
        $stats = (new \PayNL\Sdk\Model\Stats())
            ->setObject($this->methodInstance->getVersion())
            ->setExtra1($this->orderId)
            ->setExtra2($this->order->getQuoteId())
            ->setExtra3($this->order->getEntityId());

        return $stats;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function getData()
    {
        $shippingAddress = $this->getShippingAddress();
        $invoiceAddress = $this->getInvoiceAddress();
        $productData = $this->getProductData();

        $this->request->setAmount($this->paymentData['amount'])
            ->setReturnurl($this->paymentData['returnURL'])
            ->setPaymentMethodId($this->paymentMethodId)
            ->setReference($this->orderId)
            ->setExchangeUrl($this->paymentData['exchangeURL'])
            ->setCurrency($this->paymentData['currency'])
            ->setDescription($this->getDescription());

        $td = $this->methodInstance->getTransferData();
        if (is_array($td) && count($td) > 0) {
            $this->request->setTransferData([$td]);
        }

        $this->request->setExpire($this->paymentData['expire'] ?? '');
        $this->request->setStats($this->getStats());

        $order = new \PayNL\Sdk\Model\Order();
        $order->setCountryCode($this->payConfig->getLanguage());

        if (!empty($shippingAddress))   {
            $order->setShippingAddress($shippingAddress);
        }
        if (!empty($invoiceAddress)) {
            $order->setInvoiceAddress($invoiceAddress);
        }

        $customer = $this->getCustomer();
        if (!empty($customer)) {
            $customer->setCompany($this->getCompany());
            $this->request->setCustomer($customer);
        }

        if (!empty($productData)) {
            $order->setProducts($productData);
        }

        $this->request->setOrder($order);
        $this->request->setTestmode($this->testMode);
    }

    /**
     * @return \PayNL\Sdk\Model\Customer
     */
    private function getCustomer(): \PayNL\Sdk\Model\Customer
    {
        $arrBillingAddress = $this->order->getBillingAddress();

        $customer = new \PayNL\Sdk\Model\Customer();
        $customer->setFirstName(mb_substr($arrBillingAddress['firstname'] ?? '', 0, 32));
        $customer->setLastName(mb_substr($arrBillingAddress['lastname'] ?? '', 0, 64));

        $customer->setIpAddress((string)$this->getIpAddress());

        $dob = $this->additionalData['dob'] ?? null;
        if (!empty($dob)) {
            $customer->setBirthDate(mb_substr($dob, 0, 32));
        }

        $gender = $this->additionalData['gender'] ?? null;
        if (!empty($gender)) {
            $customer->setGender(mb_substr($gender, 0, 1));
        }

        $phone = payHelper::validatePhoneNumber($arrBillingAddress['telephone'] ?? '');
        if (!empty($phone)) {
            $customer->setPhone($phone);
        }

        $email = $arrBillingAddress['email'] ?? null;
        if (!empty($email)) {
            $customer->setEmail(mb_substr($email, 0, 100));
        }

        $customer->setLanguage((string)$this->payConfig->getLanguage());

        return $customer;
    }

    /**
     * @return \PayNL\Sdk\Model\Company
     */
    private function getCompany()
    {
        $arrBillingAddress = $this->order->getBillingAddress();

        $compName = trim(!empty($arrBillingAddress['company']) ? $arrBillingAddress['company'] : ($this->companyField ?? ''));
        $vat = !empty($arrBillingAddress['vat_id']) ? $arrBillingAddress['vat_id'] : ($this->vatNumber ?? null);

        $company = new \PayNL\Sdk\Model\Company();

        if (!empty($compName)) {
            $company->setName(mb_substr($compName, 0, 128));
        }

        if (!empty($this->cocNumber)) {
            $company->setCoc(mb_substr($this->cocNumber, 0, 64));
        }

        if (!empty($vat)) {
            $company->setVat(mb_substr($vat, 0, 32));
        }

        if (!empty($arrBillingAddress['country_id']) && !empty($compName)) {
            $company->setCountryCode(mb_substr($arrBillingAddress['country_id'], 0, 2));
        }
        return $company;
    }

    /**
     * @return PayOrder
     * @throws PayException
     */
    public function create(): PayOrder
    {
        $config = $this->payConfig->getPayConfig();

        $this->getData();

        $this->request->setServiceId($serviceId = $this->payConfig->getServiceId());

        return $this->request->setConfig($config)->start();
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
     * @param string $companyField
     * @return $this
     */
    public function setCompanyField($companyField)
    {
        $this->companyField = $companyField;
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
     * @return $this
     */
    public function getFinishURL()
    {
        return $this->paymentData['returnURL'];
    }

    /**
     * @param string $exchangeURL
     * @return $this
     */
    public function setExchangeURL(string $exchangeURL)
    {
        $this->paymentData['exchangeURL'] = $exchangeURL;

        $customExchangeURL = $this->payConfig->getCustomExchangeUrl();
        $customExchangeURL = is_null($customExchangeURL) ? '' : trim($customExchangeURL);

        if (!empty($customExchangeURL)) {
            $this->paymentData['exchangeURL'] = $customExchangeURL;
        }

        return $this;
    }

    /**
     * @param int $value
     * @param string $time
     * @return $this
     */
    public function setExpireData(int $value, string $time): self
    {
        if ($value > 0) {
            $offsetTime = $time === 'days' ? $value * 86400 : $value * 60;
            $this->paymentData['expire'] = date('c', time() + $offsetTime);
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
     * @param string|null $orderId
     * @return float|string|null
     */
    public function getDescription($orderId = null)
    {
        $orderId = !empty($this->orderId) ? $this->orderId : $orderId;
        if (empty($orderId)) {
            return '';
        }
        $prefix = $this->scopeConfig->getValue('payment/paynl/order_description_prefix', 'store');
        return !empty($prefix) ? $prefix . $orderId : $orderId;
    }

    /**
     * @return float|mixed|string|null
     */
    private function getIpAddress()
    {
        switch ($this->payConfig->getCustomerIp()) {
            case 'orderremoteaddress':
                $ipAddress = $this->order->getRemoteIp();
                break;
            case 'remoteaddress':
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                break;
            case 'httpforwarded':
                $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
                $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

                if (!empty($headers['X-Forwarded-For'])) {
                    $remoteIp = explode(',', $headers['X-Forwarded-For'])[0];
                } elseif (!empty($headers['HTTP_X_FORWARDED_FOR'])) {
                    $remoteIp = explode(',', $headers['HTTP_X_FORWARDED_FOR'])[0];
                }

                $ipAddress = trim($remoteIp, '[]');
                break;
            default:
                $ipAddress = paynl_get_ip();
        }

        # If the Magento IP field is too short, invalid, or localhost, then retrieve the IP manually
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP) || $ipAddress == '127.0.0.1') {
            $ipAddress = paynl_get_ip();
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
     * @return \PayNL\Sdk\Model\Address
     */
    private function getShippingAddress()
    {
        $shippingAddress = null;
        $orderShippingAddress = $this->order->getShippingAddress();
        $deliveryAddress = null;

        if (!empty($orderShippingAddress)) {
            $arrShippingAddress = $orderShippingAddress->toArray();

            # Set invoice address as shipping adres in case of pickup
            if ($this->useBillingAddressInstorePickup() && class_exists(InStorePickup::class)) {
                if ($this->order->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
                    $arrBillingAddress = $this->order->getBillingAddress();
                    if (!empty($arrBillingAddress)) {
                        $arrShippingAddress = $arrBillingAddress->toArray();
                    }
                }
            }

            $shippingAddress = [
                'initials' => mb_substr($arrShippingAddress['firstname'] ?? '', 0, 32),
                'lastName' => mb_substr($arrShippingAddress['lastname'] ?? '', 0, 64),
            ];

            $arrAddress2 = paynl_split_address($arrShippingAddress['street']);

            $deliveryAddress = new \PayNL\Sdk\Model\Address();
            $deliveryAddress->setStreetName(mb_substr($arrAddress2['street'] ?? '', 0, 128));
            $deliveryAddress->setStreetNumber(mb_substr($arrAddress2['number'] ?? '', 0, 10));
            $deliveryAddress->setZipCode(mb_substr($arrShippingAddress['postcode'] ?? '', 0, 24));
            $deliveryAddress->setCity(mb_substr($arrShippingAddress['city'] ?? '', 0, 40));
            $deliveryAddress->setCountryCode($arrShippingAddress['country_id']);
        }
        return $deliveryAddress;
    }

    /**
     * @return \PayNL\Sdk\Model\Address|null
     */
    private function getInvoiceAddress()
    {
        $arrBillingAddress = $this->order->getBillingAddress();
        $invAddress = null;

        if ($arrBillingAddress) {
            $arrBillingAddress = $arrBillingAddress->toArray();
            $invAddress = new \PayNL\Sdk\Model\Address();
            $paynlSplitAddress = paynl_split_address($arrBillingAddress['street']);
            $invAddress->setStreetName(mb_substr($paynlSplitAddress['street'] ?? '', 0, 128));
            $invAddress->setStreetNumber(mb_substr($paynlSplitAddress['number'] ?? '', 0, 10));
            $invAddress->setZipCode(mb_substr($arrBillingAddress['postcode'] ?? '', 0, 24));
            $invAddress->setCity(mb_substr($arrBillingAddress['city'] ?? '', 0, 40));
            $invAddress->setCountryCode($arrBillingAddress['country_id']);
        }

        return $invAddress;
    }

    /**
     * @return Products
     */
    private function getProductData(): Products
    {
        $collectionProducts = new Products();
        $arrWeeeTax = [];

        foreach ($this->order->getAllVisibleItems() as $item) {
            $arrItem = $item->toArray();

            if ($arrItem['price_incl_tax'] != null) {
                $taxAmount = $arrItem['price_incl_tax'] - $arrItem['price'];
                $price = $arrItem['price_incl_tax'];

                if ($this->payConfig->isAlwaysBaseCurrency()) {
                    $taxAmount = $arrItem['base_price_incl_tax'] - $arrItem['base_price'];
                    $price = $arrItem['base_price_incl_tax'];
                }

                $productId = $this->payConfig->useSkuId() ? $arrItem['sku'] : $arrItem['product_id'];
                $rate = paynl_calc_vat_percentage($price, $taxAmount);

                $productIdFinal = $productId;
                $productName = $arrItem['name'] ?? 'Unknown';
                $qtyOrdered = $arrItem['qty_ordered'] ?? 0;

                if (($arrItem['product_type'] ?? null) === Configurable::TYPE_CODE) {
                    $children = $item->getChildrenItems();
                    if (!empty($children)) {
                        $child = reset($children);
                        if ($child instanceof \Magento\Sales\Model\Order\Item) {
                            $productIdFinal = $this->payConfig->useSkuId() ? $child->getSku() : $child->getProductId();
                        }
                    }
                }

                $trimmedProductId = mb_substr((string)$productIdFinal, 0, 25);
                $collectionProducts->addProduct(new PayProduct($trimmedProductId, $productName, $price, null, PayProduct::TYPE_ARTICLE, $qtyOrdered, null, $rate));

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
                                    $arrWeeeTax[$weeeTitle]['qty'] += 1;
                                } else {
                                    $arrWeeeTax[$weeeTitle] = [
                                        'id' => 'weee-' . substr(uniqid(), 0, 6),
                                        'name' => $weeeTitle,
                                        'price' => $weeePrice,
                                        'tax' => $weeeTaxAmount,
                                        'qty' => 1,
                                        'type' => Product::TYPE_HANDLING,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        # Shipping
        $shippingCost = $this->payConfig->isAlwaysBaseCurrency() ? $this->order->getBaseShippingInclTax() : $this->order->getShippingInclTax();
        $shippingTax = $this->payConfig->isAlwaysBaseCurrency() ? $this->order->getBaseShippingTaxAmount() : $this->order->getShippingTaxAmount();

        if ($shippingCost != 0) {
            $rate = paynl_calc_vat_percentage($shippingCost, $shippingTax);
            $collectionProducts->addProduct(new PayProduct('shipping', $this->order->getShippingDescription() ?: 'Shipping', $shippingCost, null, Product::TYPE_SHIPPING, 1, null, $rate));
        }

        # Gift Wrapping
        $gwCost = $this->payConfig->isAlwaysBaseCurrency() ? $this->order->getGwBasePriceInclTax() : $this->order->getGwPriceInclTax();
        $gwTax = $this->payConfig->isAlwaysBaseCurrency() ? $this->order->getGwBaseTaxAmount() : $this->order->getGwTaxAmount();

        if ($gwCost != 0) {
            $rate = paynl_calc_vat_percentage($gwCost, $gwTax);
            $collectionProducts->addProduct(new PayProduct($this->order->getGwId() ?: 'giftwrap', 'Gift Wrapping', $gwCost, null, Product::TYPE_HANDLING, 1, null, $rate));
        }

        # Discounts
        $discount = $this->payConfig->isAlwaysBaseCurrency() ? $this->order->getBaseDiscountAmount() : $this->order->getDiscountAmount();
        $discountTax = $this->payConfig->isAlwaysBaseCurrency() ? $this->order->getBaseDiscountTaxCompensationAmount() * -1 : $this->order->getDiscountTaxCompensationAmount() * -1;

        if ($this->payConfig->isSendDiscountTax() == 0) {
            $discountTax = 0;
        }

        if ($discount != 0) {
            $rate = paynl_calc_vat_percentage(abs($discount), abs($discountTax));
            $collectionProducts->addProduct(new PayProduct('discount', 'Discount', $discount, null, Product::TYPE_DISCOUNT, 1, null, $rate));
        }

        # WEEE-tax producten
        foreach ($arrWeeeTax as $item) {
            $rate = paynl_calc_vat_percentage($item['price'], $item['tax']);
            $collectionProducts->addProduct(new PayProduct($item['id'], $item['name'], $item['price'], null, $item['type'], $item['qty'], null, $rate));
        }

        return $collectionProducts;
    }

}
