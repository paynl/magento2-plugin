<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\PayPayment;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PayNL\Sdk\Model\Request\OrderStatusRequest;
use Magento\Framework\HTTP\Client\Curl;

class ShipmentSaveAfter implements ObserverInterface
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    private Curl $httpClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PayPayment
     */
    private $payPayment;

    /**
     * @var \Paynl\Payment\Helper\PayHelper
     */
    private $payHelper;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param Config $config
     * @param PayPayment $payPayment
     * @param PayHelper $payHelper
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        Config               $config,
        PayPayment           $payPayment,
        PayHelper            $payHelper,
        UrlInterface         $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        DeploymentConfig     $deploymentConfig,
        Curl                 $httpClient
    )
    {
        $this->config = $config;
        $this->payPayment = $payPayment;
        $this->payHelper = $payHelper;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->deploymentConfig = $deploymentConfig;
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $payOrderId
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function triggerInternalCaptureProcessing(string $payOrderId): array
    {
        $this->payHelper->logDebug('triggerInternalCaptureProcessing: ' . $payOrderId);

        # Process_secret is generated on module-install
        $secret = $this->scopeConfig->getValue('payment/paynl/process_secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $token = hash('sha256', $secret . $payOrderId);
        $base = rtrim($this->deploymentConfig->get('custom_base_url') ?? $this->urlBuilder->getBaseUrl(), '/');
        $url = $base . '/paynl/process/capture';

        try {
            $this->httpClient->addHeader("Content-Type", "application/x-www-form-urlencoded");
            $this->httpClient->post($url, ['payOrderId' => $payOrderId, 'token' => $token]);

            $responseBody = $this->httpClient->getBody();
            $response = json_decode($responseBody, true);

            if (!is_array($response)) {
                throw new \UnexpectedValueException('Invalid JSON response');
            }

            return [
                'success' => $response['success'] ?? false,
                'message' => $response['message'] ?? 'Unknown response format'
            ];
        } catch (\Exception $e) {
            $this->payHelper->logDebug('Failed to trigger internal capture processing: ' . $e->getMessage() . ' url: ' . $url);
            return ['success' => false, 'message' => 'Internal request failed'];
        }
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\PaymentMethod) {
            $this->config->setStore($order->getStore());

            if ($this->config->autoCaptureEnabled()) {
                $invoiceCheck = $this->config->sherpaEnabled() ? true : !$order->hasInvoices();

                if ($order->getState() == Order::STATE_PROCESSING && $invoiceCheck) {
                    $data = $order->getPayment()->getData();
                    $payOrderId = $data['last_trans_id'] ?? null;
                    $payOrderId = str_replace('-capture', '', $payOrderId);

                    if (!empty($payOrderId)) {
                        $bHasAmountAuthorized = !empty($data['base_amount_authorized']);
                        $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                        $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;
                        $amountPaidCheck = $this->config->sherpaEnabled() ? true : $amountPaid === null;

                        if ($bHasAmountAuthorized && $amountPaidCheck === true && $amountRefunded === null) {
                            $this->payHelper->logDebug('AUTO-CAPTURING (shipment-save-after) ' . $payOrderId, [], $order->getStore());
                            $bCaptureResult = false;
                            try {
                                $this->payHelper->logDebug('ShipmentSaveAfter observer: triggering internal capture: ' . $payOrderId);

                                $bCaptureResult = $this->triggerInternalCaptureProcessing($payOrderId);
                                $bCaptureMessage = $bCaptureResult['message'];
                                $bCaptureResult = $bCaptureResult['success'];

                            } catch (\Exception $e) {
                                $strMessage = $e->getMessage();
                                $this->payHelper->logDebug('Order Pay. error(rest): ' . $strMessage . ' EntityId: ' . $order->getEntityId(), [], $order->getStore());

                                $strFriendlyMessage = 'Failed. Errorcode: PAY-MAGENTO2-004. See docs.pay.nl for more information';

                                if (stripos($strMessage, 'Transaction not found') !== false) {
                                    $strFriendlyMessage = 'Transaction seems to be already captured/paid';
                                }
                            }

                            $order->addStatusHistoryComment(__('Pay. -  Performed auto-capture. Result: ' . ($bCaptureMessage ?? '')));

                            # Whether capture failed or succeeded, we still might have to process paid order
                            $payOrder = (new OrderStatusRequest($payOrderId))->setConfig($this->config->getPayConfig())->start();
                            if ($order->canInvoice()) {
                                if ($payOrder->isPaid()) {
                                    $this->payPayment->processPaidOrder($payOrder, $order);
                                }
                            }

                        } else {
                            $this->payHelper->logDebug('Auto-Capture conditions not met (yet). Amountpaid:' . $amountPaid . ' bHasAmountAuthorized: ' . ($bHasAmountAuthorized ? '1' : '0'), [], $order->getStore());
                        }
                    } else {
                        $this->payHelper->logDebug('Auto-Capture conditions not met (yet). No Pay-Order-id.', [], $order->getStore());
                    }
                } else {
                    $this->payHelper->logDebug('Auto-capture conditions not met (yet). State: ' . $order->getState(), [], $order->getStore());
                }
            }
        }
    }
}
