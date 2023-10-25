<?php

namespace Paynl\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Url\EncoderInterface;
use Paynl\Payment\Helper\PayHelper;

class FeatureRequest extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var File
     */
    private $file;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PayHelper
     */
    private $payHelper;

    /**
     * FeatureRequest construct
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param File $file
     * @param EncoderInterface $encoder
     * @param Header $httpHeader
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param PayHelper $payHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        File $file,
        EncoderInterface $encoder,
        Header $httpHeader,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        PayHelper $payHelper
    ) {
        $this->httpHeader = $httpHeader;
        $this->encoder = $encoder;
        $this->file = $file;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->payHelper = $payHelper;
        return parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $emailResult = $this->sendEmail();
        return $result->setData(['result' => $emailResult]);
    }

    /**
     * @return boolean
     */
    private function sendEmail()
    {
        try {
            $senderName = $this->scopeConfig->getValue('trans_email/ident_sales/name', 'store', 'default');
            $senderEmail = $this->scopeConfig->getValue('trans_email/ident_sales/email', 'store', 'default');

            $sender = [
                'name' => $senderName,
                'email' => $senderEmail,
            ];

            $postParams = $this->getRequest()->getPostValue();

            $email = !empty($postParams['feature_request_email']) ? strip_tags($postParams['feature_request_email']) : '';
            $subject = 'Magento2 Suggestion';
            $message = !empty($postParams['feature_request_message']) ? strip_tags($postParams['feature_request_message']) : '';
            $version = !empty($postParams['pay_version']) ? strip_tags($postParams['pay_version']) : '';
            $magento_version = !empty($postParams['magento_version']) ? strip_tags($postParams['magento_version']) : '';

            $body = $message;
            $body = nl2br($body);

            $templateVars = [
                'subject' => $subject,
                'body' => $body,
                'email' => $email,
                'version' => $version,
                'magento_version' => $magento_version,
            ];

            $this->payHelper->logDebug(
                'Sending Feature Request E-mail with the following user data: ',
                array("sender" => $sender, "customer_email" => $email)
            );

            $template = 'feature_request_email';

            $transport = $this->transportBuilder->setTemplateIdentifier($template)
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 'default'])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo("webshop@pay.nl")
                ->getTransport();

            if (!empty($email)){
                $this->transportBuilder->setTemplateIdentifier($template)->setReplyTo($email);
            }

            $transport->sendMessage();
            return true;
        } catch (\Exception $e) {
            $this->payHelper->logDebug('Feature Request E-mail exception: ' . $e->getMessage());
            return false;
        }
    }
}
