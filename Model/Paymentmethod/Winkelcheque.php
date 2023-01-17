<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Winkelcheque extends PaymentMethod
{
    protected $_code = 'paynl_payment_winkelcheque';

    protected function getDefaultPaymentOptionId()
    {
        return 2616;
    }
}