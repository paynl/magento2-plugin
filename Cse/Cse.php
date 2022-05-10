<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

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
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $_logger;

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

    protected $resultJsonFactory;
    private $jason;
    /**
     * @var StoreManagerInterface
     */
    private $storageManager;
    protected $request;
    /**
     * Cse constructor.
     * @param Context $context
     * @param Config $config
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param Data $jsonHelper
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        StoreManagerInterface $storeManager,
        Data $jsonHelper,
        Request $request,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Serialize\Serializer\Json $jason
    )
    {
        $this->config          = $config;
        $this->checkoutSession = $checkoutSession;
        $this->_logger         = $logger;
        $this->paymentHelper   = $paymentHelper;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->storageManager  = $storeManager;
        $this->jsonHelper      = $jsonHelper;
        $this->request = $request;
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->jason = $jason;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->messageManager = $objectManager->get(\Magento\Framework\Message\ManagerInterface::class);

        //parent::__construct($context);
    }


    /**
     * Process the encrypted transaction.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_logger->debug('In Cse');

        try {
            $order = $this->checkoutSession->getLastRealOrder();

            if(empty($order)) {
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
            if ($methodInstance instanceof Visamastercard)
            {
                $this->_logger->debug('PAY.: B Start new encrypted payment for order ' . $order->getId());

                $returnUrl = $this->storageManager->getStore()->getBaseUrl() . 'paynl/checkout/returnEncryptedTransaction';
                $paymentCompleteUrl = $this->storageManager->getStore()->getBaseUrl() . 'paynl/checkout/finish';
                $pay_encrypted_data = $this->request->getParam('pay_encrypted_data');

                $arrEncryptedTransactionResult = $methodInstance->startEncryptedTransaction($order, $pay_encrypted_data, $returnUrl);

                $arrEncryptedTransactionResult['entityId'] = $order->getEntityId();

                $this->_logger->debug('PAY.: return:  ' . print_r($arrEncryptedTransactionResult, true) );

                return $this->jsonHelper->jsonEncode($arrEncryptedTransactionResult);

            } else {
                throw new Error('PAY.: Method is not compatible for CSE');
            }
        } catch (Exception $e)
        {
            $this->_getCheckoutSession()->restoreQuote();
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_logger->critical($e);

            return  $this->jsonHelper->jsonEncode(array(
                'type' => 'error',
                'message' => $e->getMessage(),
                'trace' => ''
            ));
            /*
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode(array(
                    'type' => 'error',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ))
            );*/
        }
    }


    /**
     * status()
     *
     * @return string json result
     */
    public function status()
    {
        $params = $this->request->getParams();

        $this->_logger->debug('In status(). Params:  ' . print_r($params, true));

        $transaction_id = isset($params['transaction_id']) ? $params['transaction_id'] : null;

        $data = [];
        if (!empty($transaction_id)) {
            try {
                $this->config->configureSDK();

                $result = \Paynl\Payment::authenticationStatus($transaction_id);
                $data = $result->getData();

                $this->_logger->debug('status() -> Response:  ' . print_r($data, true));

            } catch (Exception $e)
            {
                $this->_logger->debug('Status EXCEPTION-desc: ' . $e->getMessage());
                $this->_logger->debug('Status EXCEPTION-code: ' . $e->getCode());
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

        $this->_logger->debug('In authentication(). Params:  ' . print_r($params, true));

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

            if (!empty($transId)) {
                $transaction = new Model\Authenticate\TransactionMethod();
                $transaction
                    ->setOrderId($transId)
                    ->setEntranceCode($ecode);

            } else {
                $this->_logger->debug('WIILL NOT SEE THIS RIGHT?');

                $transaction = new Model\Authenticate\Transaction();
                $transaction
                    ->setServiceId(\Paynl\Config::getServiceId())
                    ->setDescription('Lorem Ipsum')
                    ->setReference('TEST.1234')
                    ->setAmount(1)
                    ->setCurrency('EUR')
                    ->setIpAddress($_SERVER['REMOTE_ADDR'])
                    ->setLanguage('NL')
                    ->setFinishUrl('');
            }

            $cse = new Model\CSE();
            $cse->setIdentifier($payload['identifier'])
                ->setData($payload['data']);

            $payment = new Model\Payment();
            $payment->setMethod(Model\Payment::METHOD_CSE)
                    ->setCse($cse);

            if (!empty($threeds_transaction_id)) {
                $auth = new Model\Auth();
                $auth
                    ->setPayTdsAcquirerId($acquirer_id) // 134 ?
                    ->setPayTdsTransactionId($threeds_transaction_id);

                $payment->setAuth($auth);
            }

            $browser = new Model\Browser();
            $browser
                ->setJavaEnabled('false')
                ->setJavascriptEnabled('false')
                ->setLanguage('nl-NL')
                ->setColorDepth('24')
                ->setScreenWidth('1920')
                ->setScreenHeight('1080')
                ->setTz('-120');

            $payment->setBrowser($browser);

            $this->config->configureSDK();
            $data = Payment::authenticateMethod($transaction, $payment)->getData();

          #  $data['entityId'] = $order->getEntityId();

            $this->_logger->debug('In authentication(). Response:  ' . print_r($data, true));

        } catch (Exception $e)
        {
            $this->_logger->debug('In authentication(). Exception resp:  ' . print_r($e->getMessage(), true));


            $data = array(
                'result' => 0,
                'errorMessage' => $e->getMessage()
            );
        }

        return $this->jsonHelper->jsonEncode($data);

    }


    /**
     * authorization
     *
     * @return array|int[]
     *
     */
    public function authorization()
    {
        $params = $this->request->getParams();

        $this->_logger->debug('In authorization(). Params:  ' . print_r($params, true));

        $ped = $params['pay_encrypted_data'] ?? null;
        $transId = $params['transaction_id'] ?? null;
        $payOrderId = $transId;
        $ecode = $params['entrance_code'] ?? null;
        $acquirer_id = $params['acquirer_id'] ?? null;
        $threeds_transaction_id = $params['threeds_transaction_id'] ?? null;

        try {
            if(empty($ped)) {
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

            $nextAction = $data['nextAction'] ?? null;
            if($nextAction == 'verify')
            {
                $this->_logger->debug('CHANGED VERIFY TO PAID');
                $data['nextAction'] = 'paid';
            }

            if($nextAction == 'paid')
            {
                /*
                $order = $this->checkoutSession->getLastRealOrder();

                if(empty($order)) {
                    $this->_logger->debug('No order found in session, please try again');
                    throw new Error('No order found in session, please try again');
                }

                try {
                    $transaction = \Paynl\Transaction::get($payOrderId);
                } catch (\Exception $e) {
                    payHelper::logCritical($e, $params, $order->getStore());
                    $this->_logger->debug('Fout bij ophalen PAY taransaction ');
                    return $this->result->setContents('FALSE| Error fetching transaction. ' . $e->getMessage());
                }

                if ($transaction->isPaid() || $transaction->isAuthorized() || $transaction->isBeingVerified()) {
                    $payment = $order->getPayment();
                    $information = $payment->getAdditionalInformation();
                    PayHelper::checkEmpty((($information['transactionId'] ?? null) == $payOrderId), '', 1014, 'Transaction mismatch');

                    $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());

                    $result = $methodInstance->processPaidOrder($transaction, $order);
                }*/
            }

            $this->_logger->debug('In authorization(). Response:  ' . print_r($data, true));

        } catch (Exception $e)
        {
            $this->_logger->debug('In authorization(). Exception resp:  ' . print_r($e->getMessage(), true));
            $data = array(
                'result' => 0,
                'errorMessage' => $e->getMessage()
            );
        }

        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Return checkout session object
     *
     * @return Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
