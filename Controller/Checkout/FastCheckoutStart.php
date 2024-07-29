<?php

namespace Paynl\Payment\Controller\Checkout;

use Paynl\Payment\Model\PayPaymentCreateFastCheckout;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;

class FastCheckoutStart extends \Magento\Framework\App\Action\Action
{
    const FC_GENERAL_ERROR = 8000;
    const FC_DB_ERROR = 8001;
    const FC_EMPTY_BASKET = 8005;

    private $cart;
    private $payConfig;
    private $resource;
    private $remoteAddress;
    private $paymentHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Paynl\Payment\Model\Config $payConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param PaymentHelper $paymentHelper
     * @param PayHelper $payHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Paynl\Payment\Model\Config $payConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        PaymentHelper $paymentHelper,
        PayHelper $payHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->cart = $cart;
        $this->payConfig = $payConfig;
        $this->remoteAddress = $remoteAddress;
        $this->resource = $resource;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->payHelper = $payHelper;

        return parent::__construct($context);
    }

    /**
     * @return array
     */
    private function getProducts()
    {
        $products = $this->cart->getItems();
        $productArr = [];
        
        foreach ($products as $key => $product) { 
            if($product->getPrice() > 0){       
                $productArr[] = [
                    'id' => $product->getProductId(),
                    'quantity' => $product->getQty(),
                    'description' => $product->getName(),
                    'price' => $product->getPrice() * 100,
                    'currecny' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                    'type' => 'product',
                    'vatPercentage' => ($product->getPriceInclTax() - $product->getBasePrice()) / $product->getBasePrice() * 100
                ];
            }
        }
        
        return $productArr;
    }

    public function execute()
    {
        $methodInstance = $this->paymentHelper->getMethodInstance('paynl_payment_ideal');
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
                $this->storeManager->getStore()->getBaseUrl()
            ))->create();

            try {
                $connection = $this->resource->getConnection();
                $tableName = $this->resource->getTableName('pay_fast_checkout');

                $connection->insertOnDuplicate(
                    $tableName, ['payOrderId' => $payTransaction->getTransactionId(), 'products' => json_encode($arrProducts), 'storeId' => $this->storeManager->getStore()->getId(), 'orderId' => null], ['payOrderId', 'products', 'storeId', 'orderId', 'created_at']
                );
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), FastCheckoutStart::FC_DB_ERROR);
            }          

            $this->getResponse()->setNoCacheHeaders();
            $this->getResponse()->setRedirect($payTransaction->getRedirectUrl());      

        } catch (\Exception $e) {
            $message = __('Something went wrong, please try again later');
            if ($e->getCode() == FastCheckoutStart::FC_EMPTY_BASKET) {
                $message = __('Please put something in the basket');             
            } elseif ($e->getCode() == FastCheckoutStart::FC_DB_ERROR) {                
                $this->payHelper->logCritical('FC DB ERROR: ' . $e->getMessage(), []);
            } else {                
                $this->payHelper->logCritical('FC ERROR: ' . $e->getMessage(), []);
            }
        
            $this->messageManager->addExceptionMessage($e, $message);   
            $this->_redirect('checkout/cart');
        }
    }
}
