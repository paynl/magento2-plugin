<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Prontowonen extends PaymentMethod
{
    protected $_code = 'paynl_payment_prontowonen';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3039;
    }
}
