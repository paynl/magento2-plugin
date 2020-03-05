<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Multibanco
 *
 */
class Multibanco extends PaymentMethod
{
    protected $_code = 'paynl_payment_multibanco';

    protected function getDefaultPaymentOptionId()
    {
        return 2271;
    }
}
