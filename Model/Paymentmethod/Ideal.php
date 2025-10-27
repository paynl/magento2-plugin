<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Ideal extends PaymentMethod
{
    public const BANKSDISPLAYTYPE_DROPDOWN = 1;
    public const BANKSDISPLAYTYPE_LIST = 2;
    protected $_code = 'paynl_payment_ideal';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 10;
    }
 
}
