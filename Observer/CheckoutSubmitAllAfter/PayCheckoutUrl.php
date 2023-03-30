<?php

namespace Paynl\Payment\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Paynl\Payment\Model\CheckoutUrl;

class PayCheckoutUrl implements ObserverInterface
{
    /**
     * @var CheckoutUrl
     */
    private $checkoutUrl;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @param CheckoutUrl $checkoutUrl
     * @param ResponseFactory $responseFactory
     */
    public function __construct(CheckoutUrl $checkoutUrl, ResponseFactory $responseFactory)
    {
        $this->checkoutUrl = $checkoutUrl;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($url = $this->checkoutUrl->getUrl()) {
            $response = $this->responseFactory->create();
            $response->setRedirect($url);
            $response->sendResponse();
            exit;
        }
    }
}
