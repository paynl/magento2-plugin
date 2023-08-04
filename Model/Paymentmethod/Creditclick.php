<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Creditclick extends PaymentMethod
{
    protected $_code = 'paynl_payment_creditclick';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2107;
    }
}
