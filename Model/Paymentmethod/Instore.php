<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config\Source\PinMoment;
use Paynl\Payment\Model\PayPaymentCreate;

class Instore extends PaymentMethod
{
    protected $_code = 'paynl_payment_instore';

    /**
     * Paylink payment block paths
     *
     * @var string
     */
    protected $_formBlockType = \Paynl\Payment\Block\Form\Instore::class;

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1729;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @return object|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($paymentAction == 'order') {
            /** @var Order $order */
            $order = $this->getInfoInstance()->getOrder();

            $additionalData = $order->getPayment()->getAdditionalInformation();
            $terminalId = null;
            if (isset($additionalData['payment_option'])) {
                $terminalId = $additionalData['payment_option'];
            }
            if (empty($terminalId)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please select a pin-terminal'));
            }

            parent::initialize($paymentAction, $stateObject);
        }
    }

    /**
     * @param Order $order
     * @param boolean $fromAdmin
     * @return string|void
     * @throws \Exception
     */
    public function startTransaction(Order $order, $fromAdmin = false)
    {
        $store = $order->getStore();
        $redirectUrl = $store->getBaseUrl() . 'checkout/cart/';

        $additionalData = $order->getPayment()->getAdditionalInformation();
        $pinLocation = PinMoment::LOCATION_CHECKOUT;

        if ($fromAdmin === false) {
            $pinLocation = $this->getPinMoment();
            # Payment starts from checkout
            if ($pinLocation == PinMoment::LOCATION_CHOICE) {
                # Checkout user chooses pinlocation
                $pinLocation = $additionalData['pinmoment'] ?? PinMoment::LOCATION_CHECKOUT;
            }
        }

        $terminalId = null;
        if (isset($additionalData['payment_option'])) {
            $terminalId = $additionalData['payment_option'];
        }
        unset($additionalData['payment_option']);

        try {
            if (empty($terminalId)) {
                if (!$fromAdmin) {
                    $this->messageManager->addNoticeMessage(__('Please select a pin-terminal'));
                    return;
                }
                throw new \Exception(__('Please select a pin-terminal'), 201);
            }

            $this->payHelper->logDebug('pinlocation', [$pinLocation], $store);

            if ($pinLocation == PinMoment::LOCATION_PICKUP) {
                $redirectUrl = $order->getStore()->getBaseUrl() . 'paynl/checkout/finish/?entityid=' . $order->getEntityId() . '&pickup=1';
                $order->addStatusHistoryComment(__('PAY.: Payment at pick-up'));
            } else {
                $transaction = (new PayPaymentCreate($order, $this))->create();

                if ($this->getPaymentOptionId() === 1927) {
                    if (array_key_exists('terminal', $transaction->getData())){
                        $additionalData['terminal_hash'] = $transaction->getData()['terminal']['hash'];
                    } else {
                        throw new \Exception(__('Pin transaction can not be started in test mode'));
                    }

                    $redirectUrl = $transaction->getRedirectUrl();
                } else {
                    $instorePayment = \Paynl\Instore::payment(['transactionId' => $transaction->getTransactionId(), 'terminalId' => $terminalId]);
                    $additionalData['terminal_hash'] = $instorePayment->getHash();
                    $redirectUrl = $instorePayment->getRedirectUrl();
                }

                $additionalData['transactionId'] = $transaction->getTransactionId();
            }

            $additionalData['payment_option'] = $terminalId;

            $order->getPayment()->setAdditionalInformation($additionalData);
            $order->save();
        } catch (\Exception $e) {
            $this->payHelper->logCritical($e->getMessage(), [], $store);

            if ($e->getCode() == 201) {
                if ($fromAdmin) {
                    throw new \Exception(__($e->getMessage()));
                } else {
                    $this->messageManager->addNoticeMessage($e->getMessage());
                }
            } else {
                if ($fromAdmin) {
                    throw new \Exception(__('Pin transaction could not be started'));
                } else {
                    $this->messageManager->addNoticeMessage(__('Pin transaction could not be started'));
                }
            }
        }

        return $redirectUrl;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('payment_option', $data['payment_option']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['payment_option'])) {
                $paymentOption = $additional_data['payment_option'];
                $this->getInfoInstance()->setAdditionalInformation('payment_option', $paymentOption);
            }
        }
        return $this;
    }

    /**
     * @return integer|mixed
     */
    public function showPaymentOptions()
    {
        if (!empty($this->getDefaultPaymentOption()) && $this->getDefaultPaymentOption() != '0') {
            if ($this->_scopeConfig->getValue('payment/' . $this->_code . '/hide_terminal_selection', 'store') == 1) {
                return 0;
            }
        }
        return 1;
    }

    /**
     * @return array|false|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentOptions()
    {
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $cacheName = 'paynl_terminals_' . $this->getPaymentOptionId() . '_' . $storeId;
        $terminalsJson = $this->cache->load($cacheName);

        $this->paynlConfig->setStore($store);

        if (!$this->paynlConfig->isPaymentMethodActive('paynl_payment_instore')) {
            return false;
        }
        if ($terminalsJson) {
            $terminalsArr = json_decode($terminalsJson);
        } else {
            $terminalsArr = [];
            try {
                $this->paynlConfig->setStore($store);
                $this->paynlConfig->configureSDK();

                $terminals = \Paynl\Instore::getAllTerminals();
                $terminals = $terminals->getList();

                foreach ($terminals as $terminal) {
                    $terminal['visibleName'] = $terminal['name'];
                    array_push($terminalsArr, $terminal);
                }
                $this->cache->save(json_encode($terminalsArr), $cacheName);
            } catch (\Paynl\Error\Error $e) {
                return false;
            }
        }

        return $terminalsArr;
    }

    /**
     * @return mixed
     */
    public function getDefaultPaymentOption()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/default_terminal', 'store');
    }

    /**
     * @return mixed
     */
    public function getPinLocationTerminal()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/pinmoment_terminal', 'store');
    }

    /**
     * @return boolean
     */
    public function isCurrentIpValid()
    {
        $onlyAllowedIPs = $this->_scopeConfig->getValue('payment/' . $this->_code . '/exclusiveforipaddress', 'store');

        if (empty($onlyAllowedIPs)) {
            return true; # No IP is given, so all ips are valid
        }

        return in_array($this->payHelper->getClientIp(), explode(",", $onlyAllowedIPs));
    }

    /**
     * @return boolean
     */
    public function isCurrentAgentValid()
    {
        $specifiedUserAgent = $this->_scopeConfig->getValue('payment/' . $this->_code . '/exclusiveforuseragent', 'store');

        if (empty($specifiedUserAgent) || $specifiedUserAgent == 'No') {
            return true;
        }
        $currentUserAgent = $this->payHelper->getHttpUserAgent();
        if ($specifiedUserAgent != 'Custom') {
            $arr_browsers = ["Opera", "Edg", "Chrome", "Safari", "Firefox", "MSIE", "Trident"];

            $user_browser = '';
            foreach ($arr_browsers as $browser) {
                if (strpos($currentUserAgent, $browser) !== false) {
                    $user_browser = $browser;
                    break;
                }
            }
            $user_browser = ($user_browser == 'Trident') ? 'MSIE' : $user_browser;

            return $specifiedUserAgent == $user_browser;
        } else {
            $custom_useragents = $this->_scopeConfig->getValue('payment/' . $this->_code . '/exclusiveforuseragent_custom', 'store');
            if (empty($custom_useragents)) {
                return true;
            }

            $arrCustomUserAgents = explode(",", $custom_useragents);
            foreach ($arrCustomUserAgents as $custom_useragent) {
                if (strpos($currentUserAgent, $custom_useragent) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
