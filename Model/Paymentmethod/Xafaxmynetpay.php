<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Xafaxmynetpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_xafaxmynetpay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3633;
    }
}
