<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\PayPaymentCreate;
use Paynl\Transaction;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

abstract class PaymentMethod extends AbstractMethod
{
    protected $_code = 'paynl_payment_base';

    protected $_isInitializeNeeded = true;

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_canCapture = true;

    protected $_canVoid = true;

    /**
     * @var Config
     */
    public $paynlConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var PayHelper
     */
    protected $helper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    protected $graphqlVersion;

    /**
     * @var CacheInterface
     */
    public $cache;

    /**
     * @var Magento\Payment\Helper\Data
     */
    public $paymentData;

    /**
     * @var Resolver
     */
    public $getLocale;

    /**
     * @var Renderer
     */
    public $addressRenderer;

    /**
     * @var TransportBuilder
     */
    public $transportBuilder;


    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $methodLogger
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Paynl\Payment\Model\Config $paynlConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $methodLogger,
        \Magento\Sales\Model\Order\Config $orderConfig,
        OrderRepository $orderRepository,
        Config $paynlConfig,
        PayHelper $helper,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        CookieManagerInterface $cookieManager,
        CacheInterface $cache,
        Resolver $getLocale,
        Renderer $addressRenderer,
        TransportBuilder $transportBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $methodLogger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->paynlConfig = $paynlConfig;
        $this->orderRepository = $orderRepository;
        $this->orderConfig = $orderConfig;
        $this->storeManager = $storeManager;
        $this->cookieManager = $cookieManager;
        $this->cache = $cache;
        $this->paymentData = $paymentData;
        $this->getLocale = $getLocale;
        $this->addressRenderer = $addressRenderer;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * @return mixed|string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @param string $status
     * @return mixed
     */
    protected function getState($status)
    {
        $validStates = [
            Order::STATE_NEW,
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_HOLDED,
        ];

        foreach ($validStates as $state) {
            $statusses = $this->orderConfig->getStateStatuses($state, false);
            if (in_array($status, $statusses)) {
                return $state;
            }
        }
        return false;
    }

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return $this->getConfigData('instructions');
    }

    /**
     * @return array
     */
    public function getPaymentOptions()
    {
        return [];
    }

    /**
     * @return boolean
     */
    public function showPaymentOptions()
    {
        return false;
    }

    /**
     * @return integer
     */
    public function hidePaymentOptions()
    {
        return 0;
    }

    /**
     * @return integer
     */
    public function getKVK()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showkvk', 'store');
    }

    /**
     * @return integer
     */
    public function getVAT()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showvat', 'store');
    }

    /**
     * @return integer
     */
    public function getDOB()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showdob', 'store');
    }

    /**
     * @return integer
     */
    public function getDisallowedShippingMethods()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/disallowedshipping', 'store');
    }

    /**
     * @return integer
     */
    public function getCompany()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showforcompany', 'store');
    }

    /**
     * @return string
     */
    public function getCustomerGroup()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/showforgroup', 'store');
    }

    /**
     * @return integer
     */
    public function shouldHoldOrder()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/holded', 'store') == 1;
    }

    /**
     * @return integer
     */
    public function useBillingAddressInstorePickup()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/useBillingAddressInstorePickup', 'store') == 1;
    }

    /**
     * @return boolean
     */
    public function isCurrentIpValid()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function isCurrentAgentValid()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function isDefaultPaymentOption()
    {
        $default_payment_option = $this->paynlConfig->getDefaultPaymentOption();
        return ($default_payment_option == $this->_code);
    }

    /**
     * @return array
     */
    public function getTransferData()
    {
        $transferData = array();

        # Get Magento's Google Analytics cookie
        if ($this->paynlConfig->sendEcommerceAnalytics()) {
            $_gaCookie = $this->cookieManager->getCookie('_ga');
            if (!empty($_gaCookie)) {
                $_gaSplit = explode('.', $_gaCookie);
                if (isset($_gaSplit[2]) && isset($_gaSplit[3])) {
                    $transferData['gaClientId'] = $_gaSplit[2] . '.' . $_gaSplit[3];
                }
            } else {
                payHelper::logDebug('Cookie empty for GA', array());
            }
        } else {
            payHelper::logDebug('GA to PAY. not enabled.', array());
        }

        return $transferData;
    }

    /**
     * @param string $version
     * @return void
     */
    public function setGraphqlVersion($version)
    {
        $this->graphqlVersion = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $version = substr('magento2 ' . $this->paynlConfig->getVersion() . ' | ' . $this->paynlConfig->getMagentoVersion() . ' | ' . $this->paynlConfig->getPHPVersion(), 0, 64);
        if (!empty($this->graphqlVersion)) {
            $version .= ' | ' . $this->graphqlVersion;
        }

        return $version;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @return object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $status = $this->getConfigData('order_status');

        $stateObject->setState($this->getState($status));
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);

        $sendEmail = $this->_scopeConfig->getValue('payment/' . $this->_code . '/send_new_order_email', 'store');

        $payment = $this->getInfoInstance();
        /** @var Order $order */
        $order = $payment->getOrder();

        if ($sendEmail == 'after_payment') {
            //prevent sending the order confirmation
            $order->setCanSendNewEmailFlag(false);
        }

        $this->orderRepository->save($order);

        return parent::initialize($paymentAction, $stateObject);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return object
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $this->paynlConfig->setStore($order->getStore());
        $this->paynlConfig->configureSDK();

        $transactionId = $payment->getParentTransactionId();
        $transactionId = str_replace('-capture', '', $transactionId);

        try {
            Transaction::refund($transactionId, $amount);
        } catch (\Exception $e) {
            $docsLink = 'https://docs.pay.nl/plugins#magento2-errordefinitions';

            $message = strtolower($e->getMessage());
            if (substr($message, 0, 19) == '403 - access denied') {
                $message = 'PAY. could not authorize this refund. Errorcode: PAY-MAGENTO2-001. See for more information ' . $docsLink;
            } else {
                $message = 'PAY. could not process this refund (' . $message . '). Errorcode: PAY-MAGENTO2-002. Transaction: ' . $transactionId . '. More info: ' . $docsLink;
            }

            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return object
     */
    public function capture(InfoInterface $payment, $amount)
    {
        try {
            $payment->setAdditionalInformation('manual_capture', 'true');
            $order = $payment->getOrder();
            $order->save();
            $this->paynlConfig->setStore($order->getStore());
            $this->paynlConfig->configureSDK();
            $transactionId = $payment->getParentTransactionId();
            Transaction::capture($transactionId);
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());
            payHelper::logCritical('Pay. could not process capture (' . $message . '). Transaction: ' . $transactionId . '. OrderId: ' . $order->getIncrementId());
        }
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @return object
     */
    public function void(InfoInterface $payment)
    {
        try {
            $order = $payment->getOrder();
            $this->paynlConfig->setStore($order->getStore());
            $this->paynlConfig->configureSDK();
            $transactionId = $payment->getParentTransactionId();
            Transaction::void($transactionId);
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());
            payHelper::logCritical('Pay. could not process void (' . $message . '). Transaction: ' . $transactionId . '. OrderId: ' . $order->getIncrementId());
        }
        return $this;
    }

    /**
     * @param Order $order
     * @return string|void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function startTransaction(Order $order)
    {
        try {
            $transaction = (new PayPaymentCreate($order, $this))->create();
        } catch (\Exception $e) {
            payHelper::logCritical('Transaction start failed: ' . $e->getMessage() . ' | ' . $e->getCode());
            $this->messageManager->addNoticeMessage(payHelper::getFriendlyMessage($e->getMessage()));
            return $order->getStore()->getBaseUrl() . 'checkout/cart/index';
        }

        payHelper::logDebug('Transaction: ' . $transaction->getTransactionId());
        $order->getPayment()->setAdditionalInformation('transactionId', $transaction->getTransactionId());
        $this->paynlConfig->setStore($order->getStore());

        if ($this->shouldHoldOrder()) {
            $order->hold();
        }

        $this->orderRepository->save($order);

        return $transaction->getRedirectUrl();
    }

    /**
     * @return integer
     */
    public function getPaymentOptionId()
    {
        $paymentOptionId = $this->getConfigData('payment_option_id');

        if (empty($paymentOptionId)) {
            $paymentOptionId = $this->getDefaultPaymentOptionId();
        }

        return $paymentOptionId;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return object
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            if (isset($data['kvknummer'])) {
                $this->getInfoInstance()->setAdditionalInformation('kvknummer', $data['kvknummer']);
            }
            if (isset($data['vatnumber'])) {
                $this->getInfoInstance()->setAdditionalInformation('vatnumber', $data['vatnumber']);
            }
            if (isset($data['dob'])) {
                $this->getInfoInstance()->setAdditionalInformation('dob', $data['dob']);
            }
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();

            if (isset($additional_data['kvknummer'])) {
                $this->getInfoInstance()->setAdditionalInformation('kvknummer', $additional_data['kvknummer']);
            }

            if (isset($additional_data['vatnumber'])) {
                $this->getInfoInstance()->setAdditionalInformation('vatnumber', $additional_data['vatnumber']);
            }

            if (isset($additional_data['billink_agree'])) {
                $this->getInfoInstance()->setAdditionalInformation('billink_agree', $additional_data['billink_agree']);
            }

            if (isset($additional_data['dob'])) {
                $this->getInfoInstance()->setAdditionalInformation('dob', $additional_data['dob']);
            }
        }
        return $this;
    }

    /**
     * @return integer the default payment option id
     */
    abstract protected function getDefaultPaymentOptionId();
}
