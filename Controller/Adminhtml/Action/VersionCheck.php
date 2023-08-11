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

    /**
     * Logs construct
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param File $file
     * @param EncoderInterface $encoder
     * @param Header $httpHeader
     */
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

    /**
     * @return mixed
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $version = $this->getVersion();

        return $result->setData(['version' => $version]);
    }

    /**
     * @return string
     */
    private function getVersion()
    {
        $url = 'https://api.github.com/repos/paynl/magento2-plugin/releases';
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'User-Agent:' . $this->httpHeader->getHttpUserAgent()));

        $context = stream_context_create($options);

        try {
            $output = $this->file->fileGetContents($url, false, $context);
            $json = json_decode($output);

            $response = '';
            if (isset($json[0])) {
                $response = $json[0]->tag_name;
            }
        } catch (\Exception $e) {
            $response = '';
        }

        return $response;
    }
}
