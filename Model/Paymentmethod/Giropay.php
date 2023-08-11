<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Giropay extends PaymentMethod
{
    protected $_code = 'paynl_payment_giropay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 694;
    }
}
