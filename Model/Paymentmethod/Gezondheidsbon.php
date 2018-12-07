<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Ideal
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Gezondheidsbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_gezondheidsbon';

    protected function getDefaultPaymentOptionId()
    {
        return 812;
    }
}