<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Afterpay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Afterpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_afterpay';

    protected function getDefaultPaymentOptionId()
    {
        return 739;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('dob', $data['dob']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();

            if (isset($additional_data['dob'])) {
                $this->getInfoInstance()->setAdditionalInformation('dob', $additional_data['dob']);
            }

        }
        return $this;
    }
}
