<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Sofort extends PaymentMethod
{
    protected $_code = 'paynl_payment_sofort';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4761;
    }
}
