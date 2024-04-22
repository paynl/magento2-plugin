<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Obscured extends Field
{
    protected $configPath;
    protected $_template = 'Paynl_Payment::system/config/obscured.phtml';

    /**
     *
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, RequestInterface $request)
    {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) // phpcs:ignore
    {
        $this->configPath = $element->getData('field_config')['config_path'];
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());
        return $this->_toHtml();
    }

    /**
     *
     * @return string
     */
    public function getValue()
    {
        $data = $this->getConfigData();
        if (isset($data[$this->configPath])) {
            $data = $data[$this->configPath];
        } else {
            $data = '';
        }
        return $data;
    }

    /**
     * @return string
     */
    public function getDecryptedValue()
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

        $apiToken = trim((string) $this->scopeConfig->getValue($this->configPath, $scope, $scopeId));
        return $apiToken ?? $this->getValue();
    }
}
