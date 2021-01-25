<?php

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Alipay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Alipay extends PaymentMethod
{
    protected $_code = 'paynl_payment_alipay';

    protected function getDefaultPaymentOptionId()
    {
        return 2080;
    }
}