<?php

namespace Paynl\Payment\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Credentials extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Paynl_Payment::system/config/credentials.phtml';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @param Context      $context
     * @param array        $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('paynl/order/checkcredentials');
    }

    /**
     * @return array
     */
    public function getScope()
    {
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        $scope = 'default';
        $scopeId = 0;

        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        }
        if ($websiteId) {
            $scope = 'websites';
            $scopeId = $websiteId;
        }

        return ['scope' => $scope, 'scopeId' => $scopeId];
    }

    /**
     * @return html
     */
    public function getButtonHtml()
    {
        $html = '<tr id="test_credentials">';
        $html .= '  <td class="label"></td>';
        $html .= '  <td class="value" >';
        $html .= '  <span id="paynl_test_credentials_loading" style="display: none;">Loading...</span>';
        $html .= '  <span id="paynl_test_credentials_result" style="display: none;"></span>';
        $html .= '  </td>';
        $html .= '</tr>';
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'test_credentials', 'label' => __('Test Credentials')]);
        return $button->toHtml() . $html;
    }
}
