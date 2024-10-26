<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPaymentCreateFastCheckout;

class FastCheckoutStart extends \Magento\Framework\App\Action\Action
{
    public const FC_GENERAL_ERROR = 8000;
    public const FC_EMPTY_BASKET = 8005;
    public const FC_ESITMATE_ERROR = 8006;
    public const FC_SHIPPING_ERROR = 8007;

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
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagementInterface;

    /**
     * @var CacheInterface
     */
    public $cache;

    /**
     * @param Context $context
     * @param Cart $cart
     * @param PaymentHelper $paymentHelper
     * @param PayHelper $payHelper
     * @param StoreManagerInterface $storeManager
     * @param ShippingMethodManagementInterface $shippingMethodManagementInterface
     * @param CacheInterface $cache
     */
    public function __construct(
        Context $context,
        Cart $cart,
        PaymentHelper $paymentHelper,
        PayHelper $payHelper,
        StoreManagerInterface $storeManager,
        ShippingMethodManagementInterface $shippingMethodManagementInterface,
        CacheInterface $cache
    ) {
        $this->cart = $cart;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->payHelper = $payHelper;
        $this->shippingMethodManagementInterface = $shippingMethodManagementInterface;
        $this->cache = $cache;

        return parent::__construct($context);
    }

    /**
     * @param quote $quote
     * @param array $params
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function quoteSetDummyData($quote, $params)
    {
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();

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
            'country_id' => $params['selected_estimate_country'] ?? 'NL',
            'region' => '',
            'postcode' => $params['selected_estimate_zip'] ?? '1234AB',
            'telephone' => 'phone',
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 1,
        );

        $billingAddress = $quote->getBillingAddress()->addData($dummyData);
        $shippingAddress = $quote->getShippingAddress()->addData($dummyData);

        $quote->save();

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();

        $shippingData = $this->shippingMethodManagementInterface->getList($quote->getId());
        $shippingMethodsAvaileble = [];
        foreach ($shippingData as $shipping) {
            $code = $shipping->getCarrierCode() . '_' . $shipping->getMethodCode();
            if ($code != 'instore_pickup' && $code != 'instore_instore') {
                $shippingMethodsAvaileble[$code] = $code;
            }
        }

        if (isset($params['fallbackShippingMethod']) && !empty($params['fallbackShippingMethod']) && !empty($shippingMethodsAvaileble[$params['fallbackShippingMethod']])) {
            $shippingMethod = $params['fallbackShippingMethod'];
        } else {
            if ($store->getConfig('payment/paynl_payment_ideal/fast_checkout_use_estimate_selection') == 2) {
                if (isset($params['selected_estimate_shipping']) && empty($shippingMethodsAvaileble[$params['selected_estimate_shipping']])) {
                    throw new \Exception('Shipping method not availeble', FastCheckoutStart::FC_ESITMATE_ERROR);
                }
            }
            if (isset($params['selected_estimate_shipping']) && !empty($params['selected_estimate_shipping']) && !empty($shippingMethodsAvaileble[$params['selected_estimate_shipping']]) && $store->getConfig('payment/paynl_payment_ideal/fast_checkout_use_estimate_selection') > 0) { // phpcs:ignore
                $shippingMethod = $params['selected_estimate_shipping'];
            } elseif (!empty($shippingMethodsAvaileble[$store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping')])) {
                $shippingMethod = $store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping');
            } elseif (!empty($shippingMethodsAvaileble[$store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping_backup')])) {
                $shippingMethod = $store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping_backup');
            }
        }

        if (empty($shippingMethod)) {
            throw new \Exception("No shipping method availeble", FastCheckoutStart::FC_SHIPPING_ERROR);
        }

        $shippingAddress->setShippingMethod($shippingMethod);
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
            if ($product->getPriceInclTax() > 0) {
                $productArr[] = [
                    'id' => $product->getProductId(),
                    'quantity' => $product->getQty(),
                    'description' => $product->getName(),
                    'price' => $product->getPriceInclTax() * 100,
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
                'vatPercentage' => ($this->cart->getQuote()->getShippingAddress()->getBaseShippingInclTax() - $this->cart->getQuote()->getShippingAddress()->getBaseShippingAmount()) / $this->cart->getQuote()->getShippingAddress()->getBaseShippingAmount() * 100, // phpcs:ignore
            ];
        }

        return $productArr;
    }

    /**
     * @return void
     */
    public function cacheShippingMethods()
    {
        $quote = $this->cart->getQuote();
        $rates = $quote->getShippingAddress()->getAllShippingRates();
        $currency = $this->storeManager->getStore()->getCurrentCurrency();
        $shippingRates = [];
        foreach ($rates as $rate) {
            if (strpos($rate->getCode(), 'error') === false && $rate->getCode() != 'instore_pickup' && $rate->getCode() != 'instore_instore') {
                $shippingRates[$rate->getCode()] = [
                    'code' => $rate->getCode(),
                    'method' => $rate->getCarrierTitle(),
                    'title' => $rate->getMethodTitle(),
                    'price' => number_format($rate->getPrice() ?? 0, 2, '.', ''),
                    'currency' => $currency->getCurrencySymbol(),
                ];
            }
        }
        $this->cache->save(json_encode($shippingRates), 'shipping_methods_' . $this->cart->getQuote()->getId());
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $methodInstance = $this->paymentHelper->getMethodInstance('paynl_payment_ideal');
            $store = $this->storeManager->getStore();
            $params = $this->getRequest()->getParams();

            if (!isset($params['fallbackShippingMethod'])) {
                if ($store->getConfig('payment/paynl_payment_ideal/fast_checkout_use_estimate_selection') == 2) {
                    if (isset($params['selected_estimate_shipping']) && empty($params['selected_estimate_shipping'])) {
                        throw new \Exception('No estimate shipping method selected', FastCheckoutStart::FC_ESITMATE_ERROR);
                    }
                }
            }

            if (empty($store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping')) && (!isset($params['fallbackShippingMethod']) || empty($params['fallbackShippingMethod'])) && (!isset($params['selected_estimate_shipping']) || empty($params['selected_estimate_shipping']))) { // phpcs:ignore
                throw new \Exception('No shipping method selected', FastCheckoutStart::FC_SHIPPING_ERROR);
            }

            $quote = $this->cart->getQuote();
            $this->quoteSetDummyData($quote, $params);

            $arrProducts = $this->getProducts();
            $fcAmount = $this->cart->getQuote()->getGrandTotal();

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
            $message = __('Unfortunately fast checkout is currently not possible.');
            if ($e->getCode() == FastCheckoutStart::FC_EMPTY_BASKET) {
                $message = __('Please put something in the basket');
            } elseif ($e->getCode() == FastCheckoutStart::FC_ESITMATE_ERROR) {
                $message = __('Please select a shipping method from the estimate.');
            } elseif ($e->getCode() == FastCheckoutStart::FC_SHIPPING_ERROR) {
                $message = __('Selected shipping method is not available.');
            } else {
                $this->payHelper->logCritical('FC ERROR: ' . $e->getMessage(), []);
            }

            if ($store->getConfig('payment/paynl_payment_ideal/fast_checkout_use_fallback') == 1 && $e->getCode() == FastCheckoutStart::FC_SHIPPING_ERROR) {
                $this->cacheShippingMethods();
                $this->_redirect('paynl/checkout/fastcheckoutfallback');
            } else {
                $this->messageManager->addNoticeMessage($message);
                $this->_redirect('checkout/cart');
            }
        }
    }
}
