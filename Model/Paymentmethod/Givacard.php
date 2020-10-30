<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Givacard
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Givacard extends PaymentMethod
{
    protected $_code = 'paynl_payment_givacard';

    protected function getDefaultPaymentOptionId()
    {
        return 1675;
    }
}