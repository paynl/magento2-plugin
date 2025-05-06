<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Vipps extends PaymentMethod
{
    protected $_code = 'paynl_payment_vipps';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3834;
    }
}
