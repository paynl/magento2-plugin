<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Upas extends PaymentMethod
{
    protected $_code = 'paynl_payment_upas';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4283;
    }
}
