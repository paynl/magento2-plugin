<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Paypal
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Paypal extends PaymentMethod
{
    protected $_code = 'paynl_payment_paypal';

    protected function getDefaultPaymentOptionId()
    {
        return 138;
    }

}