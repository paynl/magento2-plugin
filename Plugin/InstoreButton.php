<?php

namespace Paynl\Payment\Plugin;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use \Paynl\Payment\Helper\PayHelper;
use \Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

class InstoreButton
{
    protected $messageManager;
    protected $order;
    protected $backendUrl;
    protected $urlInterface;

    /**
     * @var RequestInterface
     */
    protected $_request;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order $order,
        \Magento\Backend\Model\Url $backendUrl,
        UrlInterface $urlInterface,
        RequestInterface $request
    ) {
        $this->messageManager = $messageManager;
        $this->order = $order;
        $this->backendUrl = $backendUrl;
        $this->urlInterface = $urlInterface;
        $this->_request = $request;
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
            $order_id = $this->_request->getParams()['order_id'];
            $order = $this->order->load($order_id);
            $store = $order->getStore();
            $payment = $order->getPayment();
            $payment_method = $payment->getMethod();

            $currentUrl = $this->urlInterface->getCurrentUrl();

            if (!isset($buttonList->getItems()['paynl']['start_instore_payment'])) {
                if ($payment_method == 'paynl_payment_instore' && !$order->hasInvoices() && $store->getConfig('payment/paynl_payment_instore/show_pin_button') == 1) {
                    $instoreUrl = $this->backendUrl->getUrl('paynl/order/instore') . '?order_id=' . $order_id . '&return_url=' . urlencode($currentUrl);
                    $buttonList->add(
                        'start_instore_payment',
                        ['label' => __('Start PAY. Pin'), 'onclick' => 'setLocation(\'' . $instoreUrl . '\')', 'class' => 'save'],
                        'paynl'
                    );
                }
                $error = PayHelper::getCookie('pinError');

                if (!empty($error)) {
                    $this->messageManager->addError($error);
                }

                PayHelper::deleteCookie('pinError');
            }
        }

        return [$context, $buttonList];
    }
}
