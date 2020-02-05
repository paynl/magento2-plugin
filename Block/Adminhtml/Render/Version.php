<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Paynl\Payment\Model\Config;

/**
 * Class Version
 *
 */
class Version extends Field
{
    protected $paynlConfig;

    public function __construct(
        Context $context,
        Config $paynlConfig
    ) {
        parent::__construct($context);
        $this->paynlConfig = $paynlConfig;
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
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        $html .= '  <td class="value">' . $this->paynlConfig->getVersion() . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
