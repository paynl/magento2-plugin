<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Dinerbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_dinerbon';

    protected function getDefaultPaymentOptionId()
    {
        return 2670;
    }
}