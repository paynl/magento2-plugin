<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Postepay extends PaymentMethod
{
    protected $_code = 'paynl_payment_postepay';

    protected function getDefaultPaymentOptionId()
    {
        return 707;
    }
}
