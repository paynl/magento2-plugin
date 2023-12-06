<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Cult extends PaymentMethod
{
    protected $_code = 'paynl_payment_cult';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3177;
    }
}
