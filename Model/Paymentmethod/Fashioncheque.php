<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Fashioncheque extends PaymentMethod
{
    protected $_code = 'paynl_payment_fashioncheque';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 815;
    }
}
