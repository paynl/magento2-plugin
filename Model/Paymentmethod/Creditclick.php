<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Creditclick
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Creditclick extends PaymentMethod
{
    protected $_code = 'paynl_payment_creditclick';

    protected function getDefaultPaymentOptionId()
    {
        return 2107;
    }
}
