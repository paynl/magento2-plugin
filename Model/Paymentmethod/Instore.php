<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Sales\Model\Order;

/**
 * Class Instore
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Instore extends PaymentMethod
{
    protected $_code = 'paynl_payment_instore';

    protected function getDefaultPaymentOptionId()
    {
        return 1729;
    }

    public function startTransaction(Order $order)
    {
        $store = $order->getStore();
        $url = $store->getBaseUrl() . 'checkout/cart/';

        $additionalData = $order->getPayment()->getAdditionalInformation();
        $terminalId = null;
        if (isset($additionalData['sub_option_id'])) {
            $terminalId = $additionalData['sub_option_id'];
        }
        unset($additionalData['sub_option_id']);

        try {
            if (empty($terminalId)) {
                throw new \Exception(__('Please select a pin-terminal'), 201);
            }
            $transaction = $this->doStartTransaction($order);

            $instorePayment = \Paynl\Instore::payment(['transactionId' => $transaction->getTransactionId(), 'terminalId' => $terminalId]);

            $additionalData['transactionId'] = $transaction->getTransactionId();
            $additionalData['terminal_hash'] = $instorePayment->getHash();

            $order->getPayment()->setAdditionalInformation($additionalData);
            $order->save();

            $url = $instorePayment->getRedirectUrl();

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), array());

            if ($e->getCode() == 201) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $this->messageManager->addNoticeMessage(__('Pin transaction could not be started'));
            }
        }

        return $url;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('sub_option_id', $data['sub_option_id']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['sub_option_id'])) {
                $subOption = $additional_data['sub_option_id'];
                $this->getInfoInstance()->setAdditionalInformation('sub_option_id', $subOption);
            }
        }
        return $this;
    }

    public function getSubOptions()
    {
        $cache = $this->getCache();
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $cacheName = 'paynl_terminals_' . $this->getPaymentOptionId() . '_' . $storeId;
        $terminalsJson = $cache->load($cacheName);
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
                $cache->save(json_encode($terminalsArr), $cacheName);
            } catch (\Paynl\Error\Error $e) {
                // Probably instore is not activated, no terminals present
            }
        }
        array_unshift($terminalsArr, array(
            'id' => '',
            'name' => __('Choose the pin terminal'),
            'visibleName' => __('Choose the pin terminal')
        ));
        return $terminalsArr;
    }

    public function getDefaultSubOption()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/default_terminal', 'store');       
    }

    /**
     * @return \Magento\Framework\App\CacheInterface
     */
    private function getCache()
    {
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\App\CacheInterface $cache */
        $cache = $om->get('Magento\Framework\App\CacheInterface');
        return $cache;
    }

    public function isCurrentIpValid()
    {
        $onlyAllowedIPs = $this->_scopeConfig->getValue('payment/' . $this->_code . '/exclusiveforipaddress', 'store');

        if (empty(trim($onlyAllowedIPs))) {
            return true; # No IP is given, so all ips are valid
        }

        return in_array($this->helper->getClientIp(), explode(",", $onlyAllowedIPs));
    }

    public function isCurrentAgentValid()
    {
        $specifiedUserAgent = $this->_scopeConfig->getValue('payment/' . $this->_code . '/exclusiveforuseragent', 'store');

        if (empty(trim($specifiedUserAgent)) || $specifiedUserAgent == 'No') {
            return true;
        }
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'];
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
            if (empty(trim($custom_useragents))) {
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
