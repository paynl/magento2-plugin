<?php

namespace Paynl\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\HTTP\Header;

class VersionCheck extends Action
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
     * @var
     */
    private $httpHeader;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        File $file,
        EncoderInterface $encoder,
        Header $httpHeader
    ) {
        $this->httpHeader = $httpHeader;
        $this->encoder = $encoder;
        $this->file = $file;
        $this->resultJsonFactory = $resultJsonFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $output = json_decode($this->getVersion());

        $version = $output[0]->tag_name;

        return $result->setData(['version' => $version]);
    }

    private function getVersion()
    {
        $url ='https://api.github.com/repos/paynl/magento2-plugin/releases';
        $options = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" .
                    "User-Agent:" . $this->httpHeader->getHttpUserAgent()));

        $context = stream_context_create($options);

        return $this->file->fileGetContents($url, false, $context );
    }
}