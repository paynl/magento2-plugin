<?php

namespace Paynl\Payment\Model\Paymentmethod;

class AfterpayInternational extends PaymentMethod
{
    protected $_code = 'paynl_payment_afterpay_international';

    protected function getDefaultPaymentOptionId()
    {
        return 2561;
    }
}
