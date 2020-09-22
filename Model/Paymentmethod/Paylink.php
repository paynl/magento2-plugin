<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

/**
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Paylink extends PaymentMethod
{
    protected $_code = 'paynl_payment_paylink';

    protected function getDefaultPaymentOptionId()
    {
        return 961;
    }

    /**
     * Paylink payment block paths
     *
     * @var string
     */
    protected $_formBlockType = \Paynl\Payment\Block\Form\Paylink::class;

    // this is an admin only method
    protected $_canUseCheckout = false;

   

    public function initialize($paymentAction, $stateObject)
    {
        if ($paymentAction == 'order') {
            /** @var Order $order */
            $order = $this->getInfoInstance()->getOrder();
            $this->orderRepository->save($order);

            $transaction = $this->doStartTransaction($order);
            
            $status = $this->getConfigData('order_status');
            $url = $transaction->getRedirectUrl();

            $objectManager = \Magento\Framework\App\ObjectManager::GetInstance();
  
            $getLocale = $objectManager->get('Magento\Framework\Locale\Resolver');
            $haystack  = $getLocale->getLocale(); 
            $lang = strstr($haystack, '_', true); 
            
            $pos = strrpos($url, 'NL');
            if($pos !== false)
            {
                $url = substr_replace($url, strtoupper($lang), $pos, strlen('NL'));
            }    

            $paylinktext = __('A PAY. Paylink has been send to');

            $order->addStatusHistoryComment($paylinktext . ' ' . $order->getCustomerEmail() . '.', $status);   

            $storeManager = $objectManager->create('\Magento\Store\Model\StoreManagerInterface');
            $store = $storeManager->getStore();
     
            $supportEmail = $this->_scopeConfig->getValue('trans_email/ident_support/email', 'store');
            $senderName = $this->_scopeConfig->getValue('trans_email/ident_sales/name', 'store');
            $senderEmail = $this->_scopeConfig->getValue('trans_email/ident_sales/email', 'store');

            $sender = [
                'name' => $senderName,
                'email' => $senderEmail,
            ];      

            $customerEmail = array($order->getCustomerEmail());

            $templateVars = array(
                'store' => $store,
                'customer_name' =>  $order->getCustomerName(),
                'paylink' => $url,
                'support_email' => $supportEmail,
                'current_language' => $lang
            );

            $transportBuilder = $objectManager->create('\Magento\Framework\Mail\Template\TransportBuilder');

            $transport = $transportBuilder->setTemplateIdentifier('paylink_email_template')
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
            ->setTemplateVars($templateVars)
            ->setFrom($sender)
            ->addTo($customerEmail)
            ->setReplyTo($supportEmail)            
            ->getTransport();               
            $transport->sendMessage();
            
            parent::initialize($paymentAction, $stateObject);
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->getInfoInstance()->setAdditionalInformation('valid_days', $data->getData('additional_data')['valid_days']);

        return parent::assignData($data);
    }
}