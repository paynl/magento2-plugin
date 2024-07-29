<?php

namespace Paynl\Payment\Model;

class PayPaymentProcessFastCheckout
{
    protected $_pageFactory;
    protected $context;
    protected $_storeManager;
    protected $_product;
    protected $_formkey;
    protected $quote;
    protected $quoteManagement;
    protected $customerFactory;
    protected $customerRepository;
    protected $remoteAddress;
    protected $resource;
    protected $orderService;
    protected $orderFactory;
    protected $paynlConfig;
    protected $payPayment;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Paynl\Payment\Model\Config $paynlConfig,
        \Paynl\Payment\Model\PayPayment $payPayment
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->remoteAddress = $remoteAddress;
        $this->resource = $resource;
        $this->orderFactory = $orderFactory;
        $this->paynlConfig = $paynlConfig;
        $this->payPayment = $payPayment;
    }

    /**
     * @param array $params
     * @return boolean
     */
    public function processFastCheckout($params)
    {
        $checkoutData = $params['checkoutData'];

        $customerData = $checkoutData['customer'];
        $billingAddressData = $checkoutData['billingAddress'];
        $shippingAddressData = $checkoutData['shippingAddress'];

        $payOrderId = $params['payOrderId'];

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('pay_fast_checkout');

        $select = $connection->select()->from([$tableName])->where('payOrderId = ?', $payOrderId);
        $result = $connection->fetchAll($select);

        if ($result[0]['orderId'] == null) {
            $products = json_decode($result[0]['products']);

            $store = $this->_storeManager->getStore($result[0]['storeId']);
            $websiteId = $store->getWebsiteId();

            $this->paynlConfig->setStore($store);

            $quote = $this->quote->create();
            $quote->setStore($store);

            $email = $customerData['email'];
            $customer = $this->customerFactory->create()
                ->setWebsiteId($websiteId)
                ->loadByEmail($email);

            if (!$customer->getEntityId()) {
                $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($customerData['firstName'])
                    ->setLastname($customerData['lastName'])
                    ->setEmail($email)
                    ->setPassword($email);
                $customer->save();
            }

            $customer = $this->customerRepository->getById($customer->getEntityId());
            $quote->setCurrency();

            $quote->assignCustomer($customer);
            $quote->setSendConfirmation(1);

            foreach ($products as $productArr) {
                $product = $this->_product->load($productArr->id);
                $product->setPrice($product->getPrice());
                $quote->addProduct($product, intval($productArr->quantity));
            }

            $billingAddress = $quote->getBillingAddress()->addData(array(
                'customer_address_id' => '',
                'prefix' => '',
                'firstname' => $customerData['firstName'],
                'middlename' => '',
                'lastname' => $customerData['lastName'],
                'suffix' => '',
                'company' => $customerData['company'] ?? '',
                'street' => array(
                    '0' => $billingAddressData['streetName'],
                    '1' => $billingAddressData['streetNumber'] . $billingAddressData['streetNumberAddition'],
                ),
                'city' => $billingAddressData['city'],
                'country_id' => $billingAddressData['countryCode'],
                'region' => $billingAddressData['regionCode'] ?? '',
                'postcode' => $billingAddressData['zipCode'],
                'telephone' => $customerData['phone'],
                'fax' => '',
                'vat_id' => '',
                'save_in_address_book' => 0,
            ));

            $shippingAddress = $quote->getShippingAddress()->addData(array(
                'customer_address_id' => '',
                'prefix' => '',
                'firstname' => $customerData['firstName'],
                'middlename' => '',
                'lastname' => $customerData['lastName'],
                'suffix' => '',
                'company' => $customerData['company'] ?? '',
                'street' => array(
                    '0' => $shippingAddressData['streetName'],
                    '1' => $shippingAddressData['streetNumber'] . $shippingAddressData['streetNumberAddition'],
                ),
                'city' => $shippingAddressData['city'],
                'country_id' => $shippingAddressData['countryCode'],
                'region' => $shippingAddressData['regionCode'] ?? '',
                'postcode' => $shippingAddressData['zipCode'],
                'telephone' => $customerData['phone'],
                'fax' => '',
                'vat_id' => '',
                'save_in_address_book' => 0,
            ));

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping'));
            $quote->setPaymentMethod('paynl_payment_ideal');
            $quote->setInventoryProcessed(false);
            $quote->save();
            $quote->getPayment()->importData(array('method' => 'paynl_payment_ideal'));

            $quote->collectTotals()->save();

            $service = $this->quoteManagement->submit($quote);
            $increment_id = $service->getRealOrderId();

            $order = $this->orderFactory->create()->loadByIncrementId($increment_id);
            $additionalData = $order->getPayment()->getAdditionalInformation();
            $additionalData['transactionId'] = $payOrderId;
            $order->getPayment()->setAdditionalInformation($additionalData);
            $order->save();

            $connection->insertOnDuplicate(
                $tableName, ['payOrderId' => $payOrderId, 'orderId' => $increment_id], ['payOrderId', 'orderId']
            );
        } else {
            $order = $this->orderFactory->create()->loadByIncrementId($result[0]['orderId']);
        }

        return $order;
    }

}
