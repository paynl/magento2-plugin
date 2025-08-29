<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Fashionchequebeauty extends PaymentMethod
{
    protected $_code = 'paynl_payment_fashionchequebeauty';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4428;
    }
}
