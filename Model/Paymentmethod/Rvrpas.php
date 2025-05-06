<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Rvrpas extends PaymentMethod
{
    protected $_code = 'paynl_payment_rvrpas';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4275;
    }
}
