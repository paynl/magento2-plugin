<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Yourgreengift extends PaymentMethod
{
    protected $_code = 'paynl_payment_yourgreengift';

    protected function getDefaultPaymentOptionId()
    {
        return 2925;
    }
}