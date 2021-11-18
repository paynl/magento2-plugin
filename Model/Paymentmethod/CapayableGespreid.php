<?php

namespace Paynl\Payment\Model\Paymentmethod;

class CapayableGespreid extends PaymentMethod
{
    protected $_code = 'paynl_payment_capayable_gespreid';

    protected function getDefaultPaymentOptionId()
    {
        return 1813;
    }
}
