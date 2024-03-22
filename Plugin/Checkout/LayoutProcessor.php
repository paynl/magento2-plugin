<?php

namespace Paynl\Payment\Plugin\Checkout;

class LayoutProcessor
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory $agreementCollectionFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\AddressFactory $customerAddressFactory
     * @return void
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory $agreementCollectionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->checkoutSession = $checkoutSession;
        $this->customerAddressFactory = $customerAddressFactory;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $jsLayout)
    {
        $showDOB = $this->scopeConfig->getValue('payment/paynl/show_custom_field_dob');
        $showKVK = $this->scopeConfig->getValue('payment/paynl/show_custom_field_kvk');
        $showVAT = $this->scopeConfig->getValue('payment/paynl/show_custom_field_vat');

        // Date of Birth
        if ($showDOB > 0) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['paynl_dob'] = $this->createCustomField('paynl_dob', 'date', ' Date of birth', '', 501, ($showDOB == 2), 'shippingAddress'); // phpcs:ignore
        }

        // COC number
        if ($showKVK > 0) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['paynl_coc_number'] = $this->createCustomField('paynl_coc_number', 'input', 'COC number', '', 502, ($showKVK == 2), 'shippingAddress'); // phpcs:ignore
        }

        // VAT number
        if ($showVAT > 0) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['paynl_vat_number'] = $this->createCustomField('paynl_vat_number', 'input', 'VAT number', '', 503, ($showVAT == 2), 'shippingAddress'); // phpcs:ignore
        }

        // Billing Address
        $configuration = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];
        foreach ($configuration as $paymentGroup => $groupConfig) {
            if ($showDOB > 0) {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['paynl_dob'] = $this->createCustomField('paynl_dob', 'date', ' Date of birth', '', 501, ($showDOB == 2), 'billingAddress'); // phpcs:ignore
            }
            if ($showKVK > 0) {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['paynl_coc_number'] = $this->createCustomField('paynl_coc_number', 'input', 'COC number', '', 502, ($showKVK == 2), 'billingAddress'); // phpcs:ignore
            }
            if ($showVAT > 0) {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['paynl_vat_number'] = $this->createCustomField('paynl_vat_number', 'input', 'VAT number', '', 503, ($showVAT == 2), 'billingAddress'); // phpcs:ignore
            }
        }

        return $jsLayout;
    }

    /**
     * @param string $id
     * @param string $type
     * @param string $name
     * @param string $tooltip
     * @param integer $sort_order
     * @param boolean $required
     * @param string $adressType
     * @return array
     */
    public function createCustomField($id, $type, $name, $tooltip, $sort_order, $required, $adressType)
    {
        switch ($type) {
            case 'date':
                $elementTmpl = 'ui/form/element/date';
                break;
            default:
                $elementTmpl = 'ui/form/element/input';
                break;
        }

        $customField = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => $adressType . '.custom_attributes',
                'template' => 'ui/form/field',
                'elementTmpl' => $elementTmpl,
                'options' => [],
            ],
            'dataScope' => $adressType . '.custom_attributes.' . $id,
            'label' => $name,
            'provider' => 'checkoutProvider',
            'sortOrder' => $sort_order,
            'validation' => [
                'required-entry' => $required,
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'value' => '',
        ];

        if (!empty($tooltip)) {
            $customField['config']['tooltip']['description'] = $tooltip;
        }

        return $customField;
    }
}
