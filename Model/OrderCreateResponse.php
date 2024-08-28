<?php

namespace Paynl\Payment\Model;

class OrderCreateResponse
{
    private $transid;
    private $redirectUrl;
    private $paymentReference;
    private $links;

    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param mixed $links
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function setLinks($links)
    {
        $this->links = $links;
    }

    /**
     * @param string $transactionId
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function setTransactionId($transactionId)
    {
        $this->transid = $transactionId;
    }

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transid;
    }

    /**
     * @return mixed
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param mixed $redirectUrl
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * @return mixed
     */
    public function getPaymentReference()
    {
        return $this->paymentReference;
    }

    /**
     * @param mixed $paymentReference
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function setPaymentReference($paymentReference)
    {
        $this->paymentReference = $paymentReference;
    }
}
