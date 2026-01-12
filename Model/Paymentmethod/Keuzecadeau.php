<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Keuzecadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_keuzecadeau';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4917;
    }
}
