<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Clickandbuy
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Clickandbuy extends PaymentMethod
{
    protected $_code = 'paynl_payment_clickandbuy';

    protected function getDefaultPaymentOptionId()
    {
        return 139;
    }
}