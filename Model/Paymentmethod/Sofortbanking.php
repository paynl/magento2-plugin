<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Sofortbanking
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Sofortbanking extends PaymentMethod
{
    protected $_code = 'paynl_payment_sofortbanking';

    protected function getDefaultPaymentOptionId()
    {
        return 559;
    }
}