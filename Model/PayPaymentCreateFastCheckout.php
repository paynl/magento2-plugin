<?php

namespace Paynl\Payment\Model;

use Paynl\Payment\Model\PayPaymentCreate;
use PayNL\Sdk\Model\Pay\PayOrder;
use PayNL\Sdk\Model\Product as PayProduct;
use PayNL\Sdk\Model\Products;

class PayPaymentCreateFastCheckout extends PayPaymentCreate
{
    /**
     * @var integer
     */
    private $amount;

    /**
     * @var array
     */
    private $products;

    /**
     * @var string
     */
    private $reference = '';

    /**
     * @var string
     */
    private $reservedOrderId = '';

    /**
     * @param \Paynl\Payment\Model\Paymentmethod\PaymentMethod $methodInstance
     * @param string $amount Amount to start fastCheckout with
     * @param array $products Procucts to buy with fastCheckout
     * @param string $baseUrl
     * @param string $quoteId
     * @param string $currency
     * @param string $reservedOrderId
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function __construct($methodInstance, $amount, $products, $baseUrl, $quoteId, $currency, $reservedOrderId)
    {
        $fastCheckout = parent::__construct(null, $methodInstance);

        $finishUrl = $baseUrl . 'paynl/checkout/finish/?entityid=fc';
        $exchangeUrl = $baseUrl . 'paynl/checkout/exchange/';

        $fastCheckout->setAmount($amount/100);
        $fastCheckout->setCurrency($currency);
        $fastCheckout->setFinishURL($finishUrl);
        $fastCheckout->setExchangeURL($exchangeUrl);
        $fastCheckout->setProducts($products);

        $fastCheckout->reservedOrderId = $reservedOrderId;
        $fastCheckout->reference = $quoteId;
    }

    /**
     * @param array $products
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function setProducts($products)
    {
        $this->products = $products;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function getData()
    {
        $productData = $this->getProductData();

        $this->request->setAmount($this->paymentData['amount'])
            ->setReturnurl($this->paymentData['returnURL'])
            ->setPaymentMethodId($this->paymentMethodId)
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

        if (!empty($productData)) {
            $order->setProducts($productData);
        }

        $this->request->setOrder($order);
        $this->request->setTestmode($this->testMode);
    }

    /**
     * @return \PayNL\Sdk\Model\Stats|void
     */
    private function getStats()
    {
        $stats = (new \PayNL\Sdk\Model\Stats())
            ->setObject($this->methodInstance->getVersion() . ' | fc')
            ->setExtra1((string)$this->reservedOrderId)
            ->setExtra3($this->reference);

        return $stats;
    }

    /**
     * @param array $returnArr
     * @param string $field
     * @param string $value
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function _add(&$returnArr, $field, $value) // phpcs:ignore
    {
        if (!empty($value)) {
            $returnArr = array_merge($returnArr, [$field => $value]);
        }
    }

    /**
     * @return array
     */
    private function getProductData()
    {
        $arrProducts = [];
        $collectionOfProducts = new Products();
        foreach ($this->products as $i => $arrProduct) {
            $collectionOfProducts->addProduct(
                new PayProduct(
                    $arrProduct['id'] ?? 'p' . $i,
                    $arrProduct['description'] ?? '',
                    ($arrProduct['price'] / 100),
                    $arrProduct['currecny'],
                    $arrProduct['type'] ?? '',
                    $arrProduct['quantity'] ?? 0,
                    null,
                    ($arrProduct['vatPercentage'] / 100) ?? ''
                )
            );

        }

        return $collectionOfProducts;
    }

    /**
     * @return PayOrder
     * @throws \Exception
     */
    public function create(): PayOrder
    {
        $config = $this->payConfig->getPayConfig();
        $this->getData();
        $this->request->setServiceId($serviceId = $this->payConfig->getServiceId());
        $this->request->enableFastCheckout();
        return $this->request->setConfig($config)->start();
    }

}