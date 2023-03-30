<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Paynl\Payment\Helper\PayHelper;

class ProcessPayment implements ObserverInterface
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function execute(Observer $observer)
    {
//        if ($url = $this->checkoutUrl->getUrl())
//        {
//            $response = $this->responseFactory->create();
//            $response->setRedirect($url);
//            $response->sendResponse();
//            // phpcs:ignore
//            exit;
//        }
        payHelper::logCritical('execute ', []);
        sleep(10);
        payHelper::logCritical('execute done', []);

    }
}
