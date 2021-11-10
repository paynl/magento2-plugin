<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

/**
 * Class Ideal
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Ideal extends PaymentMethod
{
    protected $_code = 'paynl_payment_ideal';

    protected function getDefaultPaymentOptionId()
    {
        return 10;
    }

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

    public function showPaymentOptions()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/bank_selection', 'store');
    }

    public function getPaymentOptions()
    {
        $show_banks = $this->_scopeConfig->getValue('payment/' . $this->_code . '/bank_selection', 'store');
        if (!$show_banks) {
            return [];
        }

        $cache = $this->getCache();
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $cacheName = 'paynl_banks_' . $this->getPaymentOptionId() . '_' . $storeId;

        $banksJson = $cache->load($cacheName);
        if ($banksJson) {
            $banks = json_decode($banksJson, true);
        } else {
            $this->paynlConfig->setStore($store);
            $this->paynlConfig->configureSDK();

            $banks = \Paynl\Paymentmethods::getBanks($this->getPaymentOptionId());
            $cache->save(json_encode($banks), $cacheName);
        }
        array_unshift($banks, array(
            'id' => '',
            'name' => __('Choose your bank'),
            'visibleName' => __('Choose your bank')
        ));

        foreach ($banks as $key => $bank) {
            $banks[$key]['logo'] = $this->paynlConfig->getIconUrlIssuer($bank['id']);
        }

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
