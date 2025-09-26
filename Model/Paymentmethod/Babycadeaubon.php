<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Babycadeaubon extends PaymentMethod
{
    protected $_code = 'paynl_payment_babycadeaubon';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4416;
    }
}
