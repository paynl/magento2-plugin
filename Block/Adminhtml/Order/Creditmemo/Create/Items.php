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
        }
        else{
            $alias = 'submit_button';
        }

        $this->unsetChild($alias);
        
        $block = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class,
            $this->getNameInLayout() . '.submit_offline_overwrite',
            ['data' => [
                'label' => __('Refund Offline'),
                'class' => 'save submit-button primary',
                'onclick' => 'confrimBox(\''. __('Offline refund').'\', \''. __('Continue to refund offline? If you also want to refund the order at PAY., you should click the Refund button.').'\', \''.$alias.'\')'
            ]]
        );        
        $this->setChild('submit_offline', $block);
     
    }

}
