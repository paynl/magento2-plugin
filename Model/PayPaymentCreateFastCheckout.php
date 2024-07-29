<?php

namespace Paynl\Payment\Model;

use Paynl\Payment\Model\PayPaymentCreate;

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
     * @param \Paynl\Payment\Model\Paymentmethod\Paymentmethod $methodInstance
     * @param string $amount Amount to start fastCheckout with
     * @param array $products Procucts to buy with fastCheckout
     * @param string $baseUrl
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function __construct($methodInstance, $amount, $products, $baseUrl, $quoteId, $currency)
    {
        $fastCheckout = parent::__construct(null, $methodInstance);

        $finishUrl = $baseUrl . 'paynl/checkout/finish/?entityid=fc';
        $exchangeUrl = $baseUrl . 'paynl/checkout/exchange/';

        $fastCheckout->setAmount($amount);
        $fastCheckout->setCurrency('EUR');
        $fastCheckout->setFinishURL($finishUrl);
        $fastCheckout->setExchangeURL($exchangeUrl);
        $fastCheckout->setProducts($products);
        $fastCheckout->reference = 'fastcheckout' . $quoteId;
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
     * @return array
     */
    public function getData()
    {
        $parameters = [
            'serviceId' => $this->payConfig->getServiceId(),
            'amount' => [
                'value' => $this->paymentData['amount'],
                'currency' => $this->paymentData['currency'],
            ],
        ];

        $parameters['paymentMethod'] = ['id' => $this->paymentMethodId];

        $this->_add($parameters, 'returnUrl', $this->paymentData['returnURL']);
        $this->_add($parameters, 'description', '');
        $this->_add($parameters, 'reference', $this->reference);
        $this->_add($parameters, 'exchangeUrl', $this->paymentData['exchangeURL']);

        $parameters['integration']['test'] = /*$this->testMode === */true;

        $optimize['flow'] = 'fastCheckout';
        $optimize['shippingAddress'] = true;
        $optimize['billingAddress'] = true;
        $optimize['contactDetails'] = '';

        $this->_add($parameters, 'optimize', $optimize);

        $orderParameters = [];
        $invoiceAddress = [];
        $productData = $this->getProductData();

        $this->_add($orderParameters, 'products', $this->getProductData());
        $this->_add($parameters, 'order', $orderParameters);

        $stats = [];
        $this->_add($stats, 'info', '');
        $this->_add($stats, 'tool', '');
        $this->_add($stats, 'object', $this->methodInstance->getVersion() . ' | fc');
        $this->_add($stats, 'extra1', '');
        $this->_add($stats, 'extra2', '');
        $this->_add($stats, 'extra3', '');
        $this->_add($parameters, 'stats', $stats);

        return $parameters;
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

        foreach ($this->products as $i => $arrProduct) {
            $product = [];
            $product['id'] = $arrProduct['id'] ?? 'p' . $i;
            $product['description'] = $arrProduct['description'] ?? '';
            $product['type'] = $arrProduct['type'] ?? '';
            $product['price'] = [
                'value' => $arrProduct['price'],
                'currency' => $arrProduct['currecny'],
            ];
            $product['quantity'] = $arrProduct['quantity'] ?? 0;
            $product['vatPercentage'] = $arrProduct['vatPercentage'] ?? '';
            $arrProducts[] = $product;
        }

        return $arrProducts;
    }

    /**
     * @return \Paynl\Result\Transaction\Start
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public function create(): OrderCreateResponse
    {
        $payload = $this->getData();

        $payload = json_encode($payload);
        $url = 'https://connect.payments.nl/v1/orders';

        $rawResponse = (array) $this->sendCurlRequest($url, $payload, $this->payConfig->getTokencode(), $this->payConfig->getApiToken());

        $redirectURL = $rawResponse['links']->redirect ?? '';

        $transaction = new OrderCreateResponse();
        $transaction->setTransactionId($rawResponse['orderId'] ?? '');
        $transaction->setRedirectUrl($redirectURL);
        $transaction->setPaymentReference($rawResponse['reference'] ?? '');
        $transaction->setLinks($rawResponse['links'] ?? '');

        return $transaction;
    }

    /**
     * @param string $requestUrl
     * @param string $payload
     * @param string $tokenCode
     * @param string $apiToken
     * @param string $method
     * @return array
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function sendCurlRequest($requestUrl, $payload, $tokenCode, $apiToken, $method = 'POST')
    {
        $authorization = base64_encode($tokenCode . ':' . $apiToken);

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $requestUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "authorization: Basic " . $authorization,
                    "content-type: application/json",
                ],
            ]
        );

        $rawResponse = curl_exec($curl);
        $response = json_decode($rawResponse);

        $error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($error) {
            throw new \Exception($error);
        } elseif (!empty($response->violations)) {
            $field = $response->violations[0]->propertyPath ?? ($response->violations[0]->code ?? '');
            throw new \Exception($field . ': ' . ($response->violations[0]->message ?? ''));
        }

        return (array) $response;
    }
}
