<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Metjebank extends PaymentMethod
{
    protected $_code = 'paynl_payment_metjebank';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5358;
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
        if ($this->_scopeConfig->getValue('payment/' . $this->_code . '/bank_selection', 'store') > 0) {
            return $this->_scopeConfig->getValue('payment/' . $this->_code . '/bank_selection', 'store');
        }
        return 0;
    }

    /**
     * @return array|false|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentOptions()
    {
        $issuersArr = [];
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $scopeId = $storeId ?? 0;

        $this->paynlConfig->setStore($store);

        if ($this->paynlConfig->isPaymentMethodActive('paynl_payment_metjebank')) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $issuers = json_decode($this->_scopeConfig->getValue('payment/paynl/issuers', $scopeType, $scopeId), true);
            if (is_array($issuers)) {
                foreach ($issuers as $issuer) {
                    array_push(
                        $issuersArr,
                        [
                            'name' => $issuer['name'],
                            'visibleName' => $issuer['name'],
                            'id' => $issuer['issuerId'],
                            'logo' => 'https://static.pay.nl' . ($issuer['image'] ?? ''),
                        ]
                    );
                }
            }
        }

        return $issuersArr;
    }
}
