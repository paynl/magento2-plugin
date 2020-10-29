<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Model\Helper\PublicKeysHelper;

/**
 * Description of ProcessEncryptedTransaction
 *
 * @author Michael Roterman <michael@pay.nl>
 */
class PublicKeys extends PayAction
{
    /**
     * @var PublicKeysHelper
     */
    private $publicKeysHelper;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * PublicKeys constructor.
     * @param Context $context
     * @param PublicKeysHelper $publicKeysHelper
     * @param Data $jsonHelper
     */
    public function __construct(
        Context $context,
        PublicKeysHelper $publicKeysHelper,
        Data $jsonHelper
    )
    {
        parent::__construct($context);

        $this->publicKeysHelper = $publicKeysHelper;
        $this->jsonHelper = $jsonHelper;
    }


    public function execute()
    {
        try {
            $this->getResponse()->setNoCacheHeaders();
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode($this->publicKeysHelper->getKeys())
            );
        } catch (\Exception $e) {
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode(array())
            );
        }
    }
}
