<?php

namespace Paynl\Payment\Plugin;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class InstoreButton
{

    protected $_messageManager;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_messageManager = $messageManager;
    }

    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $this->_request = $context->getRequest();
        if ($this->_request->getFullActionName() == 'sales_order_view') {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $order_id = $this->_request->getParams()['order_id'];
            $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $store = $order->getStore();
            $payment = $order->getPayment();
            $payment_method = $payment->getMethod();

            $website = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $currentUrl = $website . $_SERVER['REQUEST_URI'];

            if (!isset($buttonList->getItems()['paynl']['start_instore_payment'])) {
                if ($payment_method == 'paynl_payment_instore' && !$order->hasInvoices() && $store->getConfig('payment/paynl_payment_instore/show_pin_button') == 1) {
                    $instoreUrl = $website . '/paynl/order/instore/?order_id=' . $order_id . '&return_url=' . urlencode($currentUrl);
                    $buttonList->add(
                        'start_instore_payment',
                        ['label' => __('Start PAY. Pin'), 'onclick' => 'setLocation(\'' . $instoreUrl . '\')', 'class' => 'save'],
                        'paynl'
                    );
                }

                if (!empty($this->_request->getParams()['pay_error_message'])) {
                    $this->_messageManager->addError($this->_request->getParams()['pay_error_message']);
                }
            }
        }

        return [$context, $buttonList];
    }
}
