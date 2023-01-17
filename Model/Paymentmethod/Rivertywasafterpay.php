<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Rivertywasafterpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_rivertywasafterpay';

    protected function getDefaultPaymentOptionId()
    {
        return 739;
    }
}
