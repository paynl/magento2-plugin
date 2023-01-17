<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Boekenbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_boekenbon';

    protected function getDefaultPaymentOptionId()
    {
        return 2838;
    }
}