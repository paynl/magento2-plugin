<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Amex extends PaymentMethod
{
    protected $_code = 'paynl_payment_amex';

    protected function getDefaultPaymentOptionId()
    {
        return 1705;
    }
}