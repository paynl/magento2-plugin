<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Nexi extends PaymentMethod
{
    protected $_code = 'paynl_payment_nexi';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1945;
    }
}
