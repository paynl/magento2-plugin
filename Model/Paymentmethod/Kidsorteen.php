<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Kidsorteen extends PaymentMethod
{
    protected $_code = 'paynl_payment_kidsorteen';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3597;
    }
}
