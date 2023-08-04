<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Fashiongiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_fashiongiftcard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1669;
    }
}
