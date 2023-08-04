<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Dankort extends PaymentMethod
{
    protected $_code = 'paynl_payment_dankort';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1939;
    }
}
