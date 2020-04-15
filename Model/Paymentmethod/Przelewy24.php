<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Przelewy24
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Przelewy24 extends PaymentMethod
{
    protected $_code = 'paynl_payment_przelewy24';

    protected function getDefaultPaymentOptionId()
    {
        return 2151;
    }
}
