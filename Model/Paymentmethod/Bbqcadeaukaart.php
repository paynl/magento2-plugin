<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Bbqcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_bbqcadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4233;
    }
}
