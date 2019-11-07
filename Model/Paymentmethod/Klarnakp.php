<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of KlarnaKP
 *
 * @author Max Geraci <max@pay.nl>
 */
class Klarnakp extends PaymentMethod
{
    protected $_code = 'paynl_payment_klarnakp';

    protected function getDefaultPaymentOptionId()
    {
        return 2265;
    }
}
