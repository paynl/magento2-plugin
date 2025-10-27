<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Boekencadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_boekencadeau';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4749;
    }
}
