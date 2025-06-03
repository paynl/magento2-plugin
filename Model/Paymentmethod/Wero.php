<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Wero extends PaymentMethod
{
    protected $_code = 'paynl_payment_wero';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3762;
    }
}
