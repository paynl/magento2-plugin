<?php

namespace Paynl\Payment\Block\Adminhtml\Order\Creditmemo\Create;

/**
 * Class Items
 *
 */
class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->getCreditmemo()->canRefund()) {
            $alias = 'submit_offline';
        } else {
            $alias = 'submit_button';
        }

        $child = $this->getChildBlock($alias);
        $pay_onlick = 'confrimBox(\'' . __('Offline refund') . '\', \'' . __('Continue to refund offline? If you also want to refund the order at PAY., you should click the Refund button.') . '\', \'' . $alias . '\')';

        if (strlen($child->getData('on_click'))) {
            $index = 'on_click';
        } else {
            $index = 'onclick';
        }

        $child->setData($index, $pay_onlick);
    }
}
