<?php
/**
 * Copyright © 2020 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

/**
 * Description of Billink
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Billink extends PaymentMethod
{
    protected $_code = 'paynl_payment_billink';

    protected function getDefaultPaymentOptionId()
    {
        return 1672;
    }

  public function getKVK()
  {
    return $this->_scopeConfig->getValue('payment/paynl_payment_billink/showkvk', 'store');
  }

  public function getDOB()
  {
    return $this->_scopeConfig->getValue('payment/paynl_payment_billink/showdob', 'store');
  }

  public function assignData(\Magento\Framework\DataObject $data)
  {
    parent::assignData($data);

    if (is_array($data))
    {
      $this->getInfoInstance()->setAdditionalInformation('kvknummer', $data['kvknummer']);
      $this->getInfoInstance()->setAdditionalInformation('dob', $data['dob']);
    } elseif ($data instanceof \Magento\Framework\DataObject)
    {

      $additional_data = $data->getAdditionalData();

      if (isset($additional_data['kvknummer'])) {
        $this->getInfoInstance()->setAdditionalInformation('kvknummer', $additional_data['kvknummer']);
      }

      if (isset($additional_data['billink_agree'])) {
        $this->getInfoInstance()->setAdditionalInformation('billink_agree', $additional_data['billink_agree']);
      }

      if (isset($additional_data['dob'])) {
        $this->getInfoInstance()->setAdditionalInformation('dob', $additional_data['dob']);
      }

    }
    return $this;
  }


  /**
   * @return \Magento\Framework\App\CacheInterface
   */
  private function getCache()
  {
    /** @var \Magento\Framework\ObjectManagerInterface $om */
    $om = \Magento\Framework\App\ObjectManager::getInstance();
    /** @var \Magento\Framework\App\CacheInterface $cache */
    $cache = $om->get('Magento\Framework\App\CacheInterface');
    return $cache;
  }

}