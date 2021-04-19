<?php

namespace Paynl\Payment\Block\Page;

class Paylink extends \Magento\Framework\View\Element\Template
{

    /**
     * @var request
     */
    protected $request;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     * @return string
     * @since 100.2.0
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getParam($param){
        $params = $this->request->getParams();
        return (isset($params[$param])) ? $params[$param] : null;
    }
}
