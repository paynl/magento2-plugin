<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

/**
 * Description of Ideal
 *
 * @author Andy Pieters <andy@pay.nl>
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

    public function getBanksText()
    {
        return __('Choose your bank');
    }

    public function getBanks()
    {
        $show_banks = $this->_scopeConfig->getValue('payment/' . $this->_code . '/bank_selection', 'store');
        if (!$show_banks) return [];

        $cache = $this->getCache();
        $cacheName = 'paynl_banks_' . $this->getPaymentOptionId();

        $banksJson = $cache->load($cacheName);
        if ($banksJson) {
            $banks = json_decode($banksJson);
        } else {
            $this->paynlConfig->configureSDK();

            $banks = \Paynl\Paymentmethods::getBanks($this->getPaymentOptionId());
            $cache->save(json_encode($banks), $cacheName);
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
