<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Postepay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Postepay extends PaymentMethod
{
    protected $_code = 'paynl_payment_postepay';

    protected function getDefaultPaymentOptionId()
    {
        return 707;
    }
}