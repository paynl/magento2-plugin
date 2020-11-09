<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Capayable
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Capayable extends PaymentMethod
{
    protected $_code = 'paynl_payment_capayable';

    protected function getDefaultPaymentOptionId()
    {
        return 1744;
    }

}