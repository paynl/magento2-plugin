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
                'onclick' => 'confrimBox(\''. __('Offline refund').'\', \''. __('This will create an offline refund. To create an online refund, open an invoice and create credit memo for it. Do you want to continue?').'\', \''.$alias.'\')'
            ]]
        );        
        $this->setChild('submit_offline', $block);
     
    }

}
