<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Vvvgiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_vvvgiftcard';

    protected function getDefaultPaymentOptionId()
    {
        return 1714;
    }
}
