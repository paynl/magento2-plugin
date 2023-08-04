<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Multibanco extends PaymentMethod
{
    protected $_code = 'paynl_payment_multibanco';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2271;
    }
}
