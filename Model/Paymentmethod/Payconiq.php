<?php
/**
 * Copyright © 2020 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Payconiq
 *
 */
class Payconiq extends PaymentMethod
{
    protected $_code = 'paynl_payment_payconiq';

    protected function getDefaultPaymentOptionId()
    {
        return 2379;
    }
}
