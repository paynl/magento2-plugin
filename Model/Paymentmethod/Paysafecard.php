<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Ideal
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Paysafecard extends PaymentMethod
{
    protected $_code = 'paynl_payment_paysafecard';

    protected function getDefaultPaymentOptionId()
    {
        return 553;
    }
}