<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Stadspaspurmerend extends PaymentMethod
{
    protected $_code = 'paynl_payment_stadspaspurmerend';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5177;
    }
}
