<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPaymentCreate;

class Invoice extends PaymentMethod
{
    protected $_code = 'paynl_payment_invoice';

    /**
     * @return integer
     */
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

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return false|void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($paymentAction == 'order') {
            /** @var Order $order */
            $order = $this->getInfoInstance()->getOrder();
            $this->orderRepository->save($order);

            $store = $order->getStore();
            $storeId = $order->getStoreId();

            $this->paynlConfig->setStore($store);

            $transaction = (new PayPaymentCreate($order, $this))->create();

            $order->getPayment()->setAdditionalInformation('transactionId', $transaction->getTransactionId());

            $status = $this->getConfigData('order_status');
            $url = $transaction->getRedirectUrl();

            $haystack = $this->getLocale->getLocale();
            $lang = strstr($haystack, '_', true);

            $pos = strrpos($url, 'NL');
            if ($pos !== false) {
                $url = substr_replace($url, strtoupper($lang), $pos, strlen('NL'));
            }

            $send_invoice_email = $this->_scopeConfig->getValue('payment/paynl_payment_invoice/send_invoice_email', 'store', $storeId);

            if ($send_invoice_email == 0) {
                $this->addPayInvoiceComment($order, $url, $status);
            } else {
                try {
                    $customerEmail = [$order->getCustomerEmail()];

                    if (empty($customerEmail)) {
                        # Can't send email without customer email so add invoice link as a comment instead.
                        $order->addStatusHistoryComment(__('Pay.: customer e-mail is empty, cannot send e-mail'), $status)->save();
                        $this->addPayInvoiceComment($order, $url, $status);
                        return false;
                    }

                    $supportEmail = $this->_scopeConfig->getValue('trans_email/ident_support/email', 'store', $storeId);
                    $senderName = $this->_scopeConfig->getValue('trans_email/ident_sales/name', 'store', $storeId);
                    $senderEmail = $this->_scopeConfig->getValue('trans_email/ident_sales/email', 'store', $storeId);

                    $sender = [
                        'name' => $senderName,
                        'email' => $senderEmail,
                    ];

                    $orderHTML = $this->paymentData->getInfoBlockHtml(
                        $order->getPayment(),
                        $storeId
                    );

                    $show_order_in_mail = $this->_scopeConfig->getValue('payment/paynl_payment_invoice/show_order_in_mail', 'store', $storeId);
                    if ($show_order_in_mail) {
                        $show_order_in_mail = 1;
                    } else {
                        $show_order_in_mail = 0;
                    }

                    $subject = $this->_scopeConfig->getValue('payment/paynl_payment_invoice/invoice_subject', 'store', $storeId);
                    $subject = str_replace('((invoice))', '<a href="' . $url . '">' . __('PAY. invoice') . '</a>', $subject);
                    $subject = str_replace('((customer_name))', $order->getCustomerName(), $subject);
                    $subject = str_replace('((store_name))', $order->getStore()->getName(), $subject);
                    $subject = str_replace('((support_email))', '<a href="mailto:' . $supportEmail . '">' . $supportEmail . '</a>', $subject);
                    $subject = str_replace('((order_id))', $order->getIncrementId(), $subject);

                    $body = $this->_scopeConfig->getValue('payment/paynl_payment_invoice/invoice_body', 'store', $storeId);
                    $body = nl2br($body);
                    $body = str_replace('((invoice))', '<a href="' . $url . '">' . __('PAY. invoice') . '</a>', $body);
                    $body = str_replace('((customer_name))', $order->getCustomerName(), $body);
                    $body = str_replace('((store_name))', $order->getStore()->getName(), $body);
                    $body = str_replace('((support_email))', '<a href="mailto:' . $supportEmail . '">' . $supportEmail . '</a>', $body);
                    $body = str_replace('((order_id))', $order->getIncrementId(), $body);

                    $templateVars = [
                        'subject' => $subject,
                        'body' => $body,
                        'order' => $order,
                        'store' => $store,
                        'customer_name' => $order->getCustomerName(),
                        'paylink' => $url,
                        'support_email' => $supportEmail,
                        'current_language' => $lang,
                        'order_id' => $order->getEntityId(),
                        'order_increment_id' => $order->getIncrementId(),
                        'billing' => $order->getBillingAddress(),
                        'payment_html' => $orderHTML,
                        'formattedShippingAddress' => $order->getIsVirtual() ? null : $this->addressRenderer->format($order->getShippingAddress(), 'html'),
                        'formattedBillingAddress' => $this->addressRenderer->format($order->getBillingAddress(), 'html'),
                        'created_at_formatted' => $order->getCreatedAtFormatted(1),
                        'customer_name' => $order->getCustomerName(),
                        'is_not_virtual' => $order->getIsNotVirtual(),
                        'email_customer_note' => $order->getEmailCustomerNote(),
                        'frontend_status_label' => $order->getFrontendStatusLabel(),
                        'show_order_in_mail' => $show_order_in_mail,
                    ];

                    $this->payHelper->logDebug(
                        'Sending invoice E-mail with the following user data: ',
                        array("sender" => $sender, "customer_email" => $customerEmail, "support_email" => $supportEmail)
                    );
                    $template = 'paylink_email_template';
                    if ($show_order_in_mail) {
                        $template = 'paylink_email_order_template';
                    }

                    $transport = $this->transportBuilder->setTemplateIdentifier($template)
                        ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
                        ->setTemplateVars($templateVars)
                        ->setFrom($sender)
                        ->addTo($customerEmail)
                        ->setReplyTo($supportEmail)
                        ->getTransport();
                    $transport->sendMessage();

                    $invoicetext = __('A Pay. invoice has been sent to');
                    $order->addStatusHistoryComment($invoicetext . ' ' . $order->getCustomerEmail() . '.', $status)->save();
                } catch (\Exception $e) {
                    $this->payHelper->logDebug('Invoice exception: ' . $e->getMessage());
                    $order->addStatusHistoryComment(__('PAY.: Unable to send E-mail'), $status)->save();
                    $this->addPayInvoiceComment($order, $url, $status);
                }
            }

            parent::initialize($paymentAction, $stateObject);
        }
    }

    /**
     * @param Order $order
     * @return string|void
     */
    public function startTransaction(Order $order)
    {
        if ($this->_appState->getAreaCode() === \Magento\Framework\App\Area::AREA_FRONTEND) {
            $redirectUrl = $order->getStore()->getBaseUrl() . 'paynl/checkout/finish/?entityid=' . $order->getEntityId() . '&invoice=1';
            return $redirectUrl;
        }
    }

    /**
     * @param string $order
     * @param string $url
     * @param string $status
     * @return void
     */
    public function addPayInvoiceComment($order, $url, $status)
    {
        $paylinktext = __('PAY.: Here is your ');
        $postText = __('Open or copy the link to share.');
        $order->addStatusHistoryComment($paylinktext . '<A href="' . $url . '">PAY. Invoice</a>. ' . $postText, $status)->save();
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->getInfoInstance()->setAdditionalInformation('valid_days', $this->getConfigData('invoice_valid_days'));

        return parent::assignData($data);
    }
}
