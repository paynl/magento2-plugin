<?php

namespace Paynl\Payment\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\ResultFactory;

class Logs extends \Magento\Backend\App\Action
{
    protected $fileFactory;
    protected $directoryList;
    protected $resultFactory;
    protected $redirect;
    private $authorization;

    /**
     * Logs construct
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param AuthorizationInterface $authorization
     * @param ResultFactory $resultFactory
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        AuthorizationInterface $authorization,
        ResultFactory $resultFactory,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->authorization = $authorization;
        $this->resultFactory = $resultFactory;
        $this->redirect = $redirect;
        return parent::__construct($context);
    }

    /**
     * @return boolean
     */
    protected function isAllowed()
    {
        return $this->authorization->isAllowed('Paynl_Payment::logs');
    }

    /**
     * @return void
     */
    private function downloadPayLog()
    {
        # Just download the PAY. logs
        $content['type'] = 'filename';
        $content['value'] = 'log/pay.log';
        $content['rm'] = 0;
        $dir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $filePath = $dir . '/var/log/pay.log';

        if (file_exists($filePath)) {
            return $this->fileFactory->create('pay.log', $content, DirectoryList::VAR_DIR);
        }
    }

    /**
     * @return false|null
     */
    public function execute()
    {
        if (!$this->isAllowed()) {
            return false;
        }

        if (!class_exists('\ZipArchive')) {
            # Zipping is not possible, so trying to download only pay.log
            $this->downloadPayLog();
        }

        $dir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $rootPath = $dir . '/var/log';

        try {
            $bDirChange = chdir($rootPath);
        } catch (\Exception $e) {
            $bDirChange = false;
        }

        if ($bDirChange) {
            $logs = [
                $rootPath . '/pay.log',
                $rootPath . '/exception.log',
                $rootPath . '/debug.log',
                $rootPath . '/system.log'
            ];

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootPath), \RecursiveIteratorIterator::LEAVES_ONLY);
            $filesFound = [];
            foreach ($files as $name => $file) {
                if (!in_array($name, $logs)) {
                    continue;
                }
                $filesFound[$name] = $file;
            }

            if (empty($filesFound)) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->redirect->getRefererUrl());
                return $resultRedirect;
            }

            $zip = new \ZipArchive();
            $zip->open('logs.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($filesFound as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            $content['type'] = 'filename';
            $content['value'] = 'log/logs.zip';
            $content['rm'] = 1;

            return $this->fileFactory->create('logs-' . date("Y-m-d") . '.zip', $content, DirectoryList::VAR_DIR, 'application/zip');
        } else {
            return $this->downloadPayLog();
        }
    }
}
