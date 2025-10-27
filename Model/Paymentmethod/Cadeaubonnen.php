<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Cadeaubonnen extends PaymentMethod
{
    protected $_code = 'paynl_payment_cadeaubonnen';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3189;
    }
}
