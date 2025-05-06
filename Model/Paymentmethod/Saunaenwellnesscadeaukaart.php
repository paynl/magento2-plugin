<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Saunaenwellnesscadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_saunaenwellnesscadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4269;
    }
}
