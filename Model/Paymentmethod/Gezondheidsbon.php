<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Gezondheidsbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_gezondheidsbon';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 812;
    }
}
