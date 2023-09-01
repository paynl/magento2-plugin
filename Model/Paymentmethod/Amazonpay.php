<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Amazonpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_amazonpay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1903;
    }
}
