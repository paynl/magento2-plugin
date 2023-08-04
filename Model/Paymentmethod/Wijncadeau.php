<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Wijncadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_wijncadeau';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1666;
    }
}
