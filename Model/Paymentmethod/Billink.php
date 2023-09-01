<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Billink extends PaymentMethod
{
    protected $_code = 'paynl_payment_billink';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1672;
    }

  /**
   * @return \Magento\Framework\App\CacheInterface
   */
    private function getCache()
    {
      /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
      /** @var \Magento\Framework\App\CacheInterface $cache */
        $cache = $om->get(\Magento\Framework\App\CacheInterface::class);
        return $cache;
    }
}
