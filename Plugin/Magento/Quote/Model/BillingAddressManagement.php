<?php

namespace Paynl\Payment\Plugin\Magento\Quote\Model;

class BillingAddressManagement
{
  protected $logger;

  public function __construct(\Psr\Log\LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  public function beforeAssign(\Magento\Quote\Model\BillingAddressManagement $subject, $cartId, \Magento\Quote\Api\Data\AddressInterface $address, $useForShipping = false)
  {
    $extAttributes = $address->getExtensionAttributes();
    if (!empty($extAttributes)) {
      try {
        $address->setPaynlCocnumber($extAttributes->sgtPaynlCocnumber());
        $address->setPaynlVatnumber($extAttributes->getPaynlVatnumber());
      } catch (\Exception $e) {
        $this->logger->critical($e->getMessage());
      }
    }
  }
  
}