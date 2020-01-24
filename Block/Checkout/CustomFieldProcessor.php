<?php

namespace Paynl\Payment\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\App\ObjectManager;

use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\Quote\Item\AbstractItem;

use Paynl\Payment\Model\Config;

use Magento\Store\Model\Store;

class CustomFieldProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
  private $config;

  public function __construct(
    PaymentHelper $paymentHelper,
    Escaper $escaper,
    Config $paynlConfig
  )
  {
    $this->config = $paynlConfig;
  }

  public function process($jsLayout)
  {
    $bShowCompanyFields = $this->config->showCompanyFields();
    $strlangCode = 'nl';

    $strField = 'paynl_cocnumber';
    $mlCocnumber = $strlangCode == 'nl' ? 'KVK nummer' : 'Coc number';
    $customField = $this->getFieldData($strField, $mlCocnumber, $bShowCompanyFields);
    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$strField] = $customField;

    $mlVatnumber = $strlangCode == 'nl' ? 'BTW nummer' : 'VAT number';
    $strField = 'paynl_vatnumber';
    $customField = $this->getFieldData($strField, $mlVatnumber, $bShowCompanyFields);
    $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$strField] = $customField;

    return $jsLayout;
  }

  private function getFieldData($strField, $mlCocnumber, $bShowCompanyFields)
  {
    return [
      'component' => 'Magento_Ui/js/form/element/abstract',
      'config' => [
        'customScope' => 'shippingAddress.custom_attributes',
        'customEntry' => null,
        'template' => 'ui/form/field',
        'elementTmpl' => 'ui/form/element/input',
        'tooltip' => [
          'description' => 'this is what the field is for',
        ],
      ],
      'dataScope' => 'shippingAddress.custom_attributes' . '.' . $strField,
      'label' => $mlCocnumber,
      'provider' => 'checkoutProvider',
      'sortOrder' => 100,
      'validation' => [
        'required-entry' => false
      ],
      'options' => [],
      'filterBy' => null,
      'customEntry' => null,
      'visible' => $bShowCompanyFields,
      'value' => '' // value field is used to set a default value of the attribute
    ];
    return $customField;
  }

}