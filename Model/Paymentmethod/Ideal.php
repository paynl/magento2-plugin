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
            $banks = json_decode($banksJson);
        } else {
            $this->paynlConfig->setStore($store);
            $this->paynlConfig->configureSDK();

            $banks = \Paynl\Paymentmethods::getSubOptions($this->getPaymentOptionId());
            $cache->save(json_encode($banks), $cacheName);
        }
        array_unshift($banks, array(
            'id' => '',
            'name' => __('Choose your bank'),
            'visibleName' => __('Choose your bank')
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
