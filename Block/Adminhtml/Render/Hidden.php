<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;


/**
 * Class Version
 *
 */
class Hidden extends Field
{
    protected $paynlConfig;

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);

    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return '';
    }
}
