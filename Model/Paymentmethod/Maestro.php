<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Maestro extends \Paynl\Payment\Model\Paymentmethod\PaymentMethod
{
    protected $_code = 'paynl_payment_maestro';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 712;
    }
}
