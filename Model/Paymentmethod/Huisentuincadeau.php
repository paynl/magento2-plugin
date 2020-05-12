<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Huisentuincadeau
 *
 */
class Huisentuincadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_huisentuincadeau';

    protected function getDefaultPaymentOptionId()
    {
        return 2283;
    }
}
