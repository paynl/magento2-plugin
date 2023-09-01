<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Monizze extends PaymentMethod
{
    protected $_code = 'paynl_payment_monizze';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3027;
    }
}
