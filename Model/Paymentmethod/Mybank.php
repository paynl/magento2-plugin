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
class Mybank extends PaymentMethod
{
    protected $_code = 'paynl_payment_mybank';

    protected function getDefaultPaymentOptionId()
    {
        return 1855;
    }
}