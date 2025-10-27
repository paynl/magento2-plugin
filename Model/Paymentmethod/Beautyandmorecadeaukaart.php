<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Beautyandmorecadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_beautyandmorecadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4173;
    }
}
