<?php

namespace Paynl\Payment\Cse;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order\Payment\Interceptor;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Error\Error;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\Visamastercard;
use Paynl\Api\Payment\Model;
use Paynl\Payment;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Description of Cse
 *
 * @author Michael Roterman <michael@pay.nl>
 * @author Wouter Jonker <wouterl@pay.nl>
 */
class Cse
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storageManager;

    protected $request;
    /**
     * Cse constructor.
     *
     * @param Context               $context
     * @param Config                $config
     * @param Session               $checkoutSession
     * @param PaymentHelper         $paymentHelper
     * @param QuoteRepository       $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param Data                  $jsonHelper
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        QuoteRepository $quoteRepository,
        StoreManagerInterface $storeManager,
        Data $jsonHelper,
        Request $request
    ) {
        $this->config          = $config;
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper   = $paymentHelper;
        $this->quoteRepository = $quoteRepository;
        $this->storageManager  = $storeManager;
        $this->jsonHelper      = $jsonHelper;
        $this->request         = $request;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->messageManager = $objectManager->get(\Magento\Framework\Message\ManagerInterface::class);
    }

    /**
     * Process the encrypted transaction.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $params = $this->request->getParams();
        payHelper::logDebug('In Cse Class, with params:' . PHP_EOL, $params);

        try {
            $order = $this->checkoutSession->getLastRealOrder();

            if (empty($order)) {
                throw new Error('No order found in session, please try again');
            }

            # Restore the quote
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(true)->setReservedOrderId(null);
            $this->checkoutSession->replaceQuote($quote);
            $this->quoteRepository->save($quote);

            $payment = $order->getPayment();

            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());

            # Only allow visamastercard class as this is the only one that supports this behavior
            if ($methodInstance instanceof Visamastercard) {
                payHelper::logDebug('Start new encrypted payment for order ' . $order->getId());

                $returnUrl = $this->storageManager->getStore()->getBaseUrl() . 'paynl/checkout/returnEncryptedTransaction';
                $paymentCompleteUrl = $this->storageManager->getStore()->getBaseUrl() . 'paynl/checkout/finish';
                $pay_encrypted_data = $this->request->getParam('pay_encrypted_data');

                if ($this->config->isTestMode()) {
                    payHelper::logDebug('Testmode is enabled');
                    $mode = $this->request->getParam('mode') ?? '';
                    if (strtolower($mode) == 'error1') {
                        $arrEncryptedTransactionResult['result'] = 0;
                        $arrEncryptedTransactionResult['nextAction'] = 'error';
                        $arrEncryptedTransactionResult['errorMessage'] = 'Error - An error occured';
                    } elseif (strtolower($mode) == 'error2') {
                        $arrEncryptedTransactionResult['result'] = 0;
                        $arrEncryptedTransactionResult['entityId'] = 1;
                        $arrEncryptedTransactionResult['errorMessage'] = 'Helaas is het niet mogelijk om de betaling te voltooien. Het ingevoerde kaartnummer is onjuist.' .
                                ' Probeer het nogmaals en controleer uw invoer zorgvuldig.';
                    } elseif (strtolower($mode) == 'error3') {
                        throw new Exception('Exception occured');
                    } else {
                        $arrEncryptedTransactionResult['result'] = 1;
                        $arrEncryptedTransactionResult['nextAction'] = 'paid';
                        $arrEncryptedTransactionResult['orderId'] = '1234567890X12345';
                        $arrEncryptedTransactionResult['entranceCode'] = '12345';
                        $arrEncryptedTransactionResult['transaction'] = array('transactionId' => '1234567890X12345', 'entranceCode' => '12345');
                        $arrEncryptedTransactionResult['entityId'] = '1';
                    }
                } else {
                    $arrEncryptedTransactionResult = $methodInstance->startEncryptedTransaction($order, $pay_encrypted_data, $returnUrl);
                    $arrEncryptedTransactionResult['entityId'] = $order->getEntityId();
                }
            } else {
                throw new Exception('Paymentmethod is not compatible for CSE : ' . get_class($methodInstance));
            }
        } catch (Exception $e) {
            $this->_getCheckoutSession()->restoreQuote();
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            payHelper::logCritical('Execute exception: ' . $e->getMessage());
            $arrEncryptedTransactionResult = array('type' => 'error', 'errorMessage' => $e->getMessage(), 'trace' => '');
        }

        payHelper::logDebug('Execute return: ' . print_r($arrEncryptedTransactionResult, true));
        return $this->jsonHelper->jsonEncode($arrEncryptedTransactionResult);
    }

    /**
     * status()
     *
     * @return string json result
     */
    public function status()
    {
        $params = $this->request->getParams();

        payHelper::logDebug('In status(). Params:  ' . print_r($params, true));

        $transaction_id = isset($params['transaction_id']) ? $params['transaction_id'] : null;

        $data = [];
        if (!empty($transaction_id)) {
            try {
                $this->config->configureSDK();

                $result = \Paynl\Payment::authenticationStatus($transaction_id);
                $data = $result->getData();

                payHelper::logDebug('status() -> Response:  ' . print_r($data, true));
            } catch (Exception $e) {
                payHelper::logDebug('Status EXCEPTION-desc: ' . $e->getMessage());
                payHelper::logDebug('Status EXCEPTION-code: ' . $e->getCode());
            }
        }
        return $this->jsonHelper->jsonEncode($data);
    }

     /**
      * authentication()
      *
      * @return string
      */
    public function authentication()
    {
        $params = $this->request->getParams();

        payHelper::logDebug('In authentication(). Params:  ' . print_r($params, true));

        $ped = $params['pay_encrypted_data'] ?? null;
        $transId = $params['transaction_id'] ?? null;
        $ecode = $params['entrance_code'] ?? null;
        $acquirer_id = $params['acquirer_id'] ?? null;
        $threeds_transaction_id = $params['threeds_transaction_id'] ?? null;

        try {
            if (empty($ped)) {
                throw new Exception('Missing payload');
            }

            $payload = json_decode($ped, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid json');
            }

            $browserInfo = $payload['browserInfo'];

            if (!empty($transId)) {
                $transaction = new Model\Authenticate\TransactionMethod();
                $transaction->setOrderId($transId)->setEntranceCode($ecode);
            } else {
                throw new Exception('No transaction received');
            }

            $cse = new Model\CSE();
            $cse->setIdentifier($payload['identifier'])->setData($payload['data']);

            $payment = new Model\Payment();
            $payment->setMethod(Model\Payment::METHOD_CSE)->setCse($cse);

            if (!empty($threeds_transaction_id)) {
                $auth = new Model\Auth();
                $auth->setPayTdsAcquirerId($acquirer_id)->setPayTdsTransactionId($threeds_transaction_id);
                $payment->setAuth($auth);
            }

            $browser = new Model\Browser();
            $browser
                ->setJavaEnabled($browserInfo['browserJavaEnabled'] ? 'true' : 'false')
                ->setJavascriptEnabled($browserInfo['browserJavascriptEnabled'] ? 'true' : 'false')
                ->setLanguage($browserInfo['browserLanguage'])
                ->setColorDepth($browserInfo['browserColorDepth'])
                ->setScreenWidth($browserInfo['browserScreenWidth'])
                ->setScreenHeight($browserInfo['browserScreenHeight'])
                ->setTz($browserInfo['browserTZ']);

            $payment->setBrowser($browser);

            $this->config->configureSDK();
            $data = Payment::authenticateMethod($transaction, $payment)->getData();

            payHelper::logDebug('In authentication(). Response: ' . print_r($data, true));
        } catch (Exception $e) {
            payHelper::logDebug('In authentication(). Exception: ' . print_r($e->getMessage(), true));
            $data = array('result' => 0, 'errorMessage' => $e->getMessage());
        }

        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * authorization
     *
     * @return array|int[]
     */
    public function authorization()
    {
        $params = $this->request->getParams();
        payHelper::logDebug('In authorization(). Params: ' . print_r($params, true));

        $ped = $params['pay_encrypted_data'] ?? null;
        $transId = $params['transaction_id'] ?? null;
        $payOrderId = $transId;
        $ecode = $params['entrance_code'] ?? null;
        $acquirer_id = $params['acquirer_id'] ?? null;
        $threeds_transaction_id = $params['threeds_transaction_id'] ?? null;

        try {
            if (empty($ped)) {
                throw new Exception('Missing payload');
            }

            $payload = json_decode($ped, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid json');
            }

            $transaction = new Model\Authorize\Transaction();
            $transaction->setOrderId($transId)->setEntranceCode($ecode);

            $cse = new Model\CSE();
            $cse->setIdentifier($payload['identifier']);
            $cse->setData($payload['data']);

            $auth = new Model\Auth();
            $auth->setPayTdsAcquirerId($acquirer_id);
            $auth->setPayTdsTransactionId($threeds_transaction_id);

            $payment = new Model\Payment();
            $payment->setMethod(Model\Payment::METHOD_CSE);
            $payment->setCse($cse);
            $payment->setAuth($auth);

            $this->config->configureSDK();
            $data = Payment::authorize($transaction, $payment)->getData();
        } catch (Exception $e) {
            payHelper::logDebug('In authorization(). Exception: ' . print_r($e->getMessage(), true));
            $data = array(
                'result' => 0,
                'errorMessage' => $e->getMessage()
            );
        }

        payHelper::logDebug('In authorization(). Response:  ' . print_r($data, true));

        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Return checkout session object
     *
     * @phpcs:disable PSR2.Methods.MethodDeclaration
     *
     * @return Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
