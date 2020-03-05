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
class Fashiongiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_fashiongiftcard';

    protected function getDefaultPaymentOptionId()
    {
        return 1699;
    }
}