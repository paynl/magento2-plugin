<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Ideal extends PaymentMethod
{
    public const BANKSDISPLAYTYPE_DROPDOWN = 1;
    public const BANKSDISPLAYTYPE_LIST = 2;
    protected $_code = 'paynl_payment_ideal';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 10;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return object
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
     * @return integer
     */
    public function showPaymentOptions()
    {
        return $this->_scopeConfig->getValue('payment/' . $this->_code . '/bank_selection', 'store');
    }

    /**
     * @return array
     */
    public function getPaymentOptions()
    {
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
        $cache = $om->get(\Magento\Framework\App\CacheInterface::class);
        return $cache;
    }
}
