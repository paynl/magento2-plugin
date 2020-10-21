<?php
namespace Paynl\Payment\Block\Adminhtml\Order\Creditmemo;
use Magento\Framework\View\Element\Template;

class Create extends \Magento\Framework\View\Element\Template
{
    public function __construct(Template\Context $context, array $data = array())
    {
        parent::__construct($context, $data);
    }
    
}