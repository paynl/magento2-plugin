<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Spraypay extends PaymentMethod
{
    protected $_code = 'paynl_payment_spraypay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1987;
    }
}
