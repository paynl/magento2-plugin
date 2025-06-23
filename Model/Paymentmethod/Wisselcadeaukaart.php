<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Wisselcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_wisselcadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3735;
    }
}
