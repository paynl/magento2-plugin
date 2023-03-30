<?php

namespace Paynl\Payment\Model;

class CheckoutUrl
{
    /**
     * @var null|string
     */
    private $url = null;

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return void
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }
}
