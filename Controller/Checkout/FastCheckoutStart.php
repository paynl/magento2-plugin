<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPaymentCreateFastCheckout;

class FastCheckoutStart extends \Magento\Framework\App\Action\Action
{
    public const FC_GENERAL_ERROR = 8000;
    public const FC_EMPTY_BASKET = 8005;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var paymentHelper
     */
    private $paymentHelper;

    /**
     * @var PayHelper;
     */
    private $payHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param Context $context
     * @param Cart $cart
     * @param ResourceConnection $resource
     * @param RemoteAddress $remoteAddress
     * @param PaymentHelper $paymentHelper
     * @param PayHelper $payHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Cart $cart,
        ResourceConnection $resource,
        RemoteAddress $remoteAddress,
        PaymentHelper $paymentHelper,
        PayHelper $payHelper,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->cart = $cart;
        $this->remoteAddress = $remoteAddress;
        $this->resource = $resource;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->payHelper = $payHelper;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;

        return parent::__construct($context);
    }

    /**
     * @param quote $quote
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function quoteSetDummyData($quote)
    {
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();

        $email = 'fastcheckout@pay.nl';
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($email);

        if (!$customer->getEntityId()) {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname('firstname')
                ->setLastname('lastname')
                ->setEmail($email)
                ->setPassword($email);
            $customer->save();
        }

        $customer = $this->customerRepository->getById($customer->getEntityId());

        $quote->assignCustomer($customer);
        $quote->setSendConfirmation(1);

        $dummyData = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => 'firstname',
            'middlename' => '',
            'lastname' => 'lastname',
            'suffix' => '',
            'company' => '',
            'street' => array(
                '0' => 'streetname',
                '1' => 'streetnumber',
            ),
            'city' => 'city',
            'country_id' => 'NL',
            'region' => '',
            'postcode' => '1234AB',
            'telephone' => 'phone',
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 0,
        );

        $billingAddress = $quote->getBillingAddress()->addData($dummyData);
        $shippingAddress = $quote->getShippingAddress()->addData($dummyData);

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod($store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping'));

        $quote->setPaymentMethod('paynl_payment_ideal');
        $quote->setInventoryProcessed(false);
        $quote->save();

        $quote->getPayment()->importData(['method' => 'paynl_payment_ideal']);
        $quote->collectTotals()->save();
    }

    /**
     * @return array
     */
    private function getProducts()
    {
        $products = $this->cart->getItems();
        $productArr = [];

        foreach ($products as $key => $product) {
            if ($product->getPrice() > 0) {
                $productArr[] = [
                    'id' => $product->getProductId(),
                    'quantity' => $product->getQty(),
                    'description' => $product->getName(),
                    'price' => $product->getPrice() * 100,
                    'currecny' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                    'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE,
                    'vatPercentage' => ($product->getPriceInclTax() - $product->getBasePrice()) / $product->getBasePrice() * 100,
                ];
            }
        }

        if ($this->cart->getQuote()->getShippingAddress()->getShippingAmount() > 0) {
            $shippingMethodArr = explode('_', $this->cart->getQuote()->getShippingAddress()->getShippingMethod());
            $productArr[] = [
                'id' => $shippingMethodArr[0],
                'quantity' => 1,
                'description' => $shippingMethodArr[1],
                'price' => $this->cart->getQuote()->getShippingAddress()->getShippingAmount() * 100,
                'currecny' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING,
                'vatPercentage' => ($this->cart->getQuote()->getShippingAddress()->getBaseShippingInclTax() - $this->cart->getQuote()->getShippingAddress()->getBaseShippingAmount()) / $this->cart->getQuote()->getShippingAddress()->getBaseShippingAmount() * 100,
            ];
        }

        return $productArr;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $methodInstance = $this->paymentHelper->getMethodInstance('paynl_payment_ideal');

        $quote = $this->cart->getQuote();
        $this->quoteSetDummyData($quote);

        $arrProducts = $this->getProducts();

        $fcAmount = $this->cart->getQuote()->getGrandTotal();
        try {
            if (empty($fcAmount)) {
                throw new \Exception('empty amount', FastCheckoutStart::FC_EMPTY_BASKET);
            }

            $payTransaction = (new PayPaymentCreateFastCheckout(
                $methodInstance,
                $fcAmount * 100,
                $arrProducts,
                $this->storeManager->getStore()->getBaseUrl(),
                $this->cart->getQuote()->getId(),
                $this->storeManager->getStore()->getCurrentCurrencyCode()
            ))->create();

            $this->getResponse()->setNoCacheHeaders();
            $this->getResponse()->setRedirect($payTransaction->getRedirectUrl());
        } catch (\Exception $e) {
            $message = __('Something went wrong, please try again later');
            if ($e->getCode() == FastCheckoutStart::FC_EMPTY_BASKET) {
                $message = __('Please put something in the basket');
            } else {
                $this->payHelper->logCritical('FC ERROR: ' . $e->getMessage(), []);
            }

            $this->messageManager->addExceptionMessage($e, $message);
            $this->_redirect('checkout/cart');
        }
    }
}
