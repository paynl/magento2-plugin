<?php

namespace Paynl\Payment\Helper;

use Psr\Log\LoggerInterface;

class PayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    private static $objectManager;
    private static $store;

    const LOG_TYPE_CRITICAL = 'CRITICAL';
    const LOG_TYPE_DEBUG = 'DEBUG';
    const LOG_TYPE_INFO = 'INFO';
    const LOG_TYPE_NOTICE = 'NOTICE';

    public static function getObjectManager()
    {
        if (empty(self::$objectManager)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            self::$objectManager = $objectManager;
        }
        return self::$objectManager;
    }

    public static function getStore($store)
    {
        if (empty($store)) {
            if (empty(self::$store)) {
                $objectManager = self::getObjectManager();
                $store = $objectManager->get(\Magento\Store\Model\Store::class);
                self::$store = $store;
            }
            return self::$store;
        }
        return $store;
    }

    public static function hasCorrectLevel($level, $type)
    {
        if($level == 2 && $type == self::LOG_TYPE_CRITICAL){
            return true;
        }
        if($level == 1 && ($type == self::LOG_TYPE_CRITICAL || $type == self::LOG_TYPE_NOTICE)){
            return true;
        }
        if($level == 0){
            return true;
        }

        return false;
    }

    public static function log($text, $type, $params = array(), $store = null)
    {
        $objectManager = self::getObjectManager();
        $logger = $objectManager->get(\Psr\Log\LoggerInterface::class);

        $store = self::getStore($store);
        $level = $store->getConfig('payment/paynl/logging_level');
     
        if (self::hasCorrectLevel($level, $type)) {
            $prefix = 'PAY.: ';
            $text = $prefix . $text;
            if(!is_array($params)){
                $params = array();
            }
            switch ($type) {
                case self::LOG_TYPE_CRITICAL:
                    $logger->critical($text, $params);
                    break;
                case self::LOG_TYPE_NOTICE:
                    $logger->notice($text, $params);
                    break;
                case self::LOG_TYPE_INFO:
                    $logger->alert($text, $params);
                    break;
                case self::LOG_TYPE_DEBUG:
                    $logger->debug($text, $params);
                    break;
            }
        }
    }


    public function getClientIp()
    {
        $ipforward = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        return !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ipforward;
    }
}
