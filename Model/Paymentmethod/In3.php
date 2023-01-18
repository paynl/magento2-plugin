<?php

namespace Paynl\Payment\Model\Paymentmethod;

class In3 extends PaymentMethod
{
    protected $_code = 'paynl_payment_in3';

    /**
     * @return int
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1813;
    }
}
