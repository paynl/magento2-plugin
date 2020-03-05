<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Sales\Model\Order;
/**
 * Description of Instore
 *
 * @author Andy Pieters <andy@pay.nl>
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

        $additionalData = $order->getPayment()->getAdditionalInformation();
        $bankId = null;
        if (isset($additionalData['bank_id'])) {
            $bankId = $additionalData['bank_id'];
        }
        unset($additionalData['bank_id']);

        $transaction = $this->doStartTransaction($order);

        $instorePayment = \Paynl\Instore::payment([
            'transactionId' => $transaction->getTransactionId(),
            'terminalId' => $bankId
        ]);

        $additionalData['terminal_hash'] = $instorePayment->getHash();

        $order->getPayment()->setAdditionalInformation($additionalData);
        $order->save();

        return $instorePayment->getRedirectUrl();
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('bank_id', $data['bank_id']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['bank_id'])) {
                $bankId = $additional_data['bank_id'];
                $this->getInfoInstance()->setAdditionalInformation('bank_id', $bankId);
            }
        }
        return $this;
    }

    public function getBanks()
    {
        $cache = $this->getCache();
        $cacheName = 'paynl_terminals_' . $this->getPaymentOptionId();
        $banksJson = $cache->load($cacheName);
        if ($banksJson) {
            $banks = json_decode($banksJson);
        } else {
            $banks = [];
            try {
                $this->paynlConfig->configureSDK();

                $terminals = \Paynl\Instore::getAllTerminals();
                $terminals = $terminals->getList();

                foreach ($terminals as $terminal) {
                    $terminal['visibleName'] = $terminal['name'];
                    array_push($banks, $terminal);
                }
                $cache->save(json_encode($banks), $cacheName);
            } catch (\Paynl\Error\Error $e) {
                // Probably instore is not activated, no terminals present
            }
        }
        array_unshift($banks, array(
            'id' => '',
            'name' => __('Choose the pin terminal'),
            'visibleName' => __('Choose the pin terminal')
        ));
        return $banks;
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
}