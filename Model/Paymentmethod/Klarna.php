<?php
namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Klarna
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Klarna extends PaymentMethod
{
    protected $_code = 'paynl_payment_klarna';

    protected function getDefaultPaymentOptionId()
    {
        return 1717;
    }

    public function getDOB()
    {
        return $this->_scopeConfig->getValue('payment/paynl_payment_klarna/showdob', 'store');
    }
}