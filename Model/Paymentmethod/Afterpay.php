<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Afterpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_afterpay';

    protected function getDefaultPaymentOptionId()
    {
        return 739;
    }
}
