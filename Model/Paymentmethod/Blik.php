<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Blik extends PaymentMethod
{
    protected $_code = 'paynl_payment_blik';

    protected function getDefaultPaymentOptionId()
    {
        return 2856;
    }
}
