<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Trustly
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Trustly extends PaymentMethod
{
    protected $_code = 'paynl_payment_trustly';

    protected function getDefaultPaymentOptionId()
    {
        return 2718;
    }
}
