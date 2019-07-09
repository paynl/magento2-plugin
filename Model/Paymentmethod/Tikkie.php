<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Tikkie
 * @package Paynl\Payment\Model\Paymentmethod
 * @author Daan Stokhof <daan@Pay.nl>
 */
class Tikkie extends PaymentMethod
{
    protected $_code = 'paynl_payment_tikkie';

    protected function getDefaultPaymentOptionId()
    {
        return 2104;
    }
}
