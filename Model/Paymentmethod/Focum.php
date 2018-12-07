<?php
namespace Paynl\Payment\Model\Paymentmethod;

class Focum extends PaymentMethod
{
    protected $_code = 'paynl_payment_focum';

    protected function getDefaultPaymentOptionId()
    {
        return 1702;
    }
}