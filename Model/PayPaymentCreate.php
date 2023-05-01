<?php

namespace Paynl\Payment\Model;

class PayPaymentCreate
{
    /**
     * @var integer
     */
    private $amount;

    /**
     * @var string
     */
    private $finishURL;

    /**
     * @param integer $amount Amounts
     * @param string $exchangeURL
     */
    public function __construct($amount = null, $exchangeURL = null)
    {
        $this->setAmount($amount);
        $this->setFinishURL($exchangeURL);
    }

    /**
     * @param string $url Finish URL
     * @return void
     */
    public function setFinishURL($url)
    {
        $this->finishURL = $url;
    }

    /**
     * @param integer $amount
     * @return void
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }
}
