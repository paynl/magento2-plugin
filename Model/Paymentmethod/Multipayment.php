<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Multipayment
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Multipayment extends PaymentMethod
{
    protected $_code = 'paynl_payment_multipayment';

    protected function getDefaultPaymentOptionId()
    {
        return 0;
    }
}
