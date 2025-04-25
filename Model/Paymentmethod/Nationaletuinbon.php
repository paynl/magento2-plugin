<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Nationaletuinbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_nationaletuinbon';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4152;
    }
}
