<?php

namespace Paynl\Payment\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class Logs extends \Magento\Framework\App\Action\Action
{
    protected $fileFactory;
    protected $directoryList;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        return parent::__construct($context);
    }

    public function execute()
    {
        if (!class_exists('\ZipArchive')) {
            # Just download the PAY. logs         
            $content['type'] = 'filename';
            $content['value'] ='log/pay.log';
            $content['rm'] = 0;
            $this->fileFactory->create('pay.log', $content, DirectoryList::VAR_DIR);  
        }
        
        $dir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);     
        $rootPath = $dir.'/var/log';
        chdir($rootPath);

        $zip = new \ZipArchive();
        $zip->open('logs.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );       

        foreach ($files as $name => $file)
        {
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $content['type'] = 'filename';
        $content['value'] = 'log/logs.zip';
        $content['rm'] = 1;
        $this->fileFactory->create('logs-'. date("Y-m-d").'.zip', $content, DirectoryList::VAR_DIR);              
    }
}
