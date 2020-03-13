<?php
/**
 * Copyright © 2020 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller;

/**
 * Class PayAction
 * @package Paynl\Payment\Controller
 */
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

            if (count($jsonRequest) > 0) {
                $request->setParams($jsonRequest);
            }
        }

        return $request;
    }
}
