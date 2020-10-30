<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Wechatpay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Wechatpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_wechatpay';

    protected function getDefaultPaymentOptionId()
    {
        return 1978;
    }
}