<?php

namespace Paynl\Payment\Controller\Checkout;

class FastCheckoutCreateOrder extends \Magento\Framework\App\Action\Action
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
    protected $orderService;

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
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
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

        return parent::__construct($context);
    }

    public function execute()
    {

        $data = json_decode('{"id":"6693a2f4-5223-8992-1a5f-526160012372","uuid":"a1bf5261-6001-2372-5200-1705096a2372","links":{"void":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/void","abort":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/abort","debug":"https:\/\/checkout.payments.nl\/to\/checkout\/6693a2f4-5223-8992-1a5f-526160012372\/with\/debugging\/6693a2f4522389921a5f526160012372","status":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/status","approve":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/approve","capture":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/capture","decline":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/decline","checkout":"https:\/\/checkout.payments.nl\/to\/return\/6693a2f4-5223-8992-1a5f-526160012372","redirect":"https:\/\/checkout.payments.nl\/to\/return\/6693a2f4-5223-8992-1a5f-526160012372","captureAmount":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/capture\/amount","captureProducts":"https:\/\/connect.payments.nl\/v1\/orders\/6693a2f4-5223-8992-1a5f-526160012372\/capture\/products"},"amount":{"value":"10","currency":"EUR"},"status":{"code":"100","action":"PAID"},"orderId":"52001705096X2372","receipt":"","payments":[{"id":"6693a310-5223-88e3-2b77-052001705096","amount":{"value":"10","currency":"EUR"},"status":{"code":"100","action":"PAID"},"ipAddress":"","customerId":"NL93INGB0006700949","customerKey":"fd36c8fb62a554e1bd7be3a7fd7485a6","customerName":"Hr W M Jonker","customerType":"","secureStatus":"1","supplierData":{"contactDetails":{"email":"wouterjonker@hotmail.com","lastName":"Jonker","firstName":"Wouter","phoneNumber":" 31652457521"},"invoiceAddress":{"city":"Sommelsdijk","street":"Kortewegje","addition":"","lastName":"Jonker","firstName":"Wouter","postalCode":"3245XM","companyName":"","countryName":"Netherlands","houseNumber":"19"},"shippingAddress":{"city":"Sommelsdijk","street":"Kortewegje","addition":"","lastName":"Jonker","firstName":"Wouter","postalCode":"3245XM","companyName":"","countryName":"Netherlands","houseNumber":"19"}},"paymentMethod":{"id":"10","input":{"issuerId":""}},"capturedAmount":{"value":"10","currency":"EUR"},"currencyAmount":{"value":"10","currency":"EUR"},"authorizedAmount":{"value":"0","currency":"EUR"},"paymentVerificationMethod":"21"}],"createdAt":"2024-07-14T10:05:40 00:00","createdBy":"AT-0045-3283","expiresAt":"2024-07-21T10:05:40 00:00","reference":"","serviceId":"SL-5261-6001","modifiedAt":"2024-07-14T12:07:18 02:00","modifiedBy":"","completedAt":"2024-07-14T12:07:18 02:00","customerKey":"fd36c8fb62a554e1bd7be3a7fd7485a6","description":"","integration":{"test":""},"checkoutData":{"customer":{"email":"wouterjonker@hotmail.com","phone":" 31652457521","gender":"","locale":"","company":"","lastname":"Jonker","firstname":"Wouter","ipAddress":"","reference":"NL93INGB0006700949","dateOfBirth":""},"billingAddress":{"city":"Sommelsdijk","zipCode":"3245XM","lastName":"Jonker","firstName":"Wouter","regionCode":"","streetName":"Kortewegje","countryCode":"NL","streetNumber":"19","streetNumberAddition":""},"shippingAddress":{"city":"Sommelsdijk","zipCode":"3245XM","lastName":"Jonker","firstName":"Wouter","regionCode":"","streetName":"Kortewegje","countryCode":"NL","streetNumber":"19","streetNumberAddition":""}},"capturedAmount":{"value":"10","currency":"EUR"},"authorizedAmount":{"value":"0","currency":"EUR"},"manualTransferCode":"1000 0520 0170 5096"}');
        
        $customerData = $data->checkoutData->customer;        
        $billingAddressData = $data->checkoutData->billingAddress;        
        $shippingAddressData = $data->checkoutData->shippingAddress;    

        $payOrderId = '2531784984X99580';

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('pay_fast_checkout');

        $select = $connection->select()->from([$tableName])->where('payOrderId = ?', $payOrderId);
        $result = $connection->fetchAll($select);        

        $products = json_decode($result[0]['products']);
      
        $productids=array(1);
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        $quote=$this->quote->create(); 
        $quote->setStore($store); 
  
        $email = $customerData->email; 
        $customer = $this->customerFactory->create()
                    ->setWebsiteId($websiteId)
                    ->loadByEmail($email);      

        if(!$customer->getEntityId()){
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($customerData->firstname)
                    ->setLastname($customerData->lastname)
                    ->setEmail($email) 
                    ->setPassword($email);
            $customer->save();
        }

        $customer= $this->customerRepository->getById($customer->getEntityId());
        $quote->setCurrency();

        $quote->assignCustomer($customer);
    
        $quote->setSendConfirmation(1);
        foreach($products as $productArr){      
            $product = $this->_product->load($productArr->id);
            $quote->addProduct($product, intval(array('qty' => $productArr->qty)));
        }

        $billingAddress = $quote->getBillingAddress()->addData(array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $customerData->firstname,
            'middlename' => '',
            'lastname' => $customerData->lastname,
            'suffix' => '',
            'company' => $customerData->company, 
            'street' => array(
                    '0' => $billingAddressData->streetName,
                    '1' => $billingAddressData->streetNumber . $billingAddressData->streetNumberAddition
                ),
            'city' => $billingAddressData->city,
            'country_id' => $billingAddressData->countryCode,
            'region' => $billingAddressData->regionCode,
            'postcode' => $billingAddressData->zipCode,
            'telephone' => $customerData->phone,
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 1
        ));   
        
        $shippingAddress = $quote->getShippingAddress()->addData(array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $customerData->firstname,
            'middlename' => '',
            'lastname' => $customerData->lastname,
            'suffix' => '',
            'company' => $customerData->company, 
            'street' => array(
                    '0' => $shippingAddressData->streetName,
                    '1' => $shippingAddressData->streetNumber . $shippingAddressData->streetNumberAddition
                ),
            'city' => $shippingAddressData->city,
            'country_id' => $shippingAddressData->countryCode,
            'region' => $shippingAddressData->regionCode,
            'postcode' => $shippingAddressData->zipCode,
            'telephone' => $customerData->phone,
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 1
        ));       

        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('flatrate_flatrate')
                        ->setPaymentMethod('paynl_payment_ideal');
        $quote->setPaymentMethod('paynl_payment_ideal'); 
        $quote->setInventoryProcessed(false);
        $quote->save(); 

        $quote->getPayment()->importData(array('method' => 'paynl_payment_ideal'));
    
   
        $quote->collectTotals()->save();

        $service = $this->quoteManagement->submit($quote);
        $increment_id = $service->getRealOrderId();
    
        $quote = $customer = $service = null;

        echo __('Order created successfully with order id '.$increment_id);
    }
}
