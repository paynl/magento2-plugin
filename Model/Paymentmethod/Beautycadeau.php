<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Beautycadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_beautycadeau';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3144;
    }
}
