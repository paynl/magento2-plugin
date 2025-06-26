<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Scholierenpas extends PaymentMethod
{
    protected $_code = 'paynl_payment_scholierenpas';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4434;
    }
}
