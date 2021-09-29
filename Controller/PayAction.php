<?php

namespace Paynl\Payment\Controller;

abstract class PayAction extends \Magento\Framework\App\Action\Action
{
    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        $request = parent::getRequest();
        if ($request->isPost() && count($request->getParams()) <= 0) {
            $jsonRequest = json_decode($request->getContent(), true);

            if (is_array($jsonRequest)) {
                $request->setParams($jsonRequest);
            }
        }

        return $request;
    }
}
