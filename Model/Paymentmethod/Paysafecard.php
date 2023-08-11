<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Paysafecard extends PaymentMethod
{
    protected $_code = 'paynl_payment_paysafecard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 553;
    }
}
