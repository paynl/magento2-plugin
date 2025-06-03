<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Bizum extends PaymentMethod
{
    protected $_code = 'paynl_payment_bizum';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3843;
    }
}
