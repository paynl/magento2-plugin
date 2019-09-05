<?php

namespace Paynl\Payment\Model\Paymentmethod;


class Applepay extends PaymentMethod
{
    protected $_code = 'paynl_payment_applepay';

    protected function getDefaultPaymentOptionId()
    {
        return 2277;
    }
}