<?php

namespace Paynl\Payment\Helper;

use Psr\Log\LoggerInterface;
use \Paynl\Payment\Model\Config\Source\LogOptions;


class PayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PAY_LOG_PREFIX = 'PAY.: ';

    private static $objectManager;
    private static $store;

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
        if ($level == LogOptions::LOG_ONLY_CRITICAL && $type == 'critical') {
            return true;
        }
        if ($level == LogOptions::LOG_CRITICAL_NOTICE && ($type == 'critical' || $type == 'notice')) {
            return true;
        }
        if ($level == LogOptions::LOG_ALL) {
            return true;
        }

        return false;
    }

    public static function logCritical($text, $params = array(), $store = null)
    {
        self::writeLog($text, 'critical', $params, $store);
    }

    public static function logNotice($text, $params = array(), $store = null)
    {
        self::writeLog($text, 'notice', $params, $store);
    }

    public static function logInfo($text, $params = array(), $store = null)
    {
        self::writeLog($text, 'info', $params, $store);
    }

    public static function logDebug($text, $params = array(), $store = null)
    {
        self::writeLog($text, 'debug', $params, $store);
    }

    /**
     * Logs while bypassing the loglevel setting.
     *
     * @param $text
     * @param array $params
     * @param null $store
     */
    public static function log($text, $params = array(), $store = null)
    {
        $objectManager = self::getObjectManager();
        $logger = $objectManager->get(\Paynl\Payment\Logging\Logger::class);
        $logger->notice($text, $params);
    }

    public static function writeLog($text, $type, $params, $store)
    {
        $objectManager = self::getObjectManager();
        $store = self::getStore($store);
        $level = $store->getConfig('payment/paynl/logging_level');

        if (self::hasCorrectLevel($level, $type)) {
            if (!is_array($params)) {
                $params = array();
            }
            $logger = $objectManager->get(\Paynl\Payment\Logging\Logger::class);
            switch ($type) {
                case 'critical':
                    $logger->critical($text, $params);
                    break;
                case 'notice':
                    $logger->notice($text, $params);
                    break;
                case 'info':
                    $logger->info($text, $params);
                    break;
                case 'debug':
                    $logger->debug($text, $params);
                    break;
            }
        }
    }

    public static function setCookie($cookieName, $value)
    {
        $objectManager = self::getObjectManager();
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $cookieMetadataFactory = $objectManager->get(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);

        $metadata = $cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(300)
            ->setSecure(false)
            ->setPath('/')
            ->setHttpOnly(false);

        $cookieManager->setPublicCookie(
            $cookieName,
            $value,
            $metadata
        );
    }

    public static function getCookie($cookieName)
    {
        $objectManager = self::getObjectManager();
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        return $cookieManager->getCookie($cookieName);
    }

    public static function deleteCookie($cookieName)
    {
        $objectManager = self::getObjectManager();
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $cookieMetadataFactory = $objectManager->get(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);
        if ($cookieManager->getCookie($cookieName)) {
            $metadata = $cookieMetadataFactory->createPublicCookieMetadata();
            $metadata->setPath('/');
            return $cookieManager->deleteCookie($cookieName, $metadata);
        }
    }

    public static function checkProcessing($payOrderId)
    {
        $objectManager = self::getObjectManager();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('pay_processing');

        $select = "SELECT * FROM `" . $tableName . "` WHERE `payOrderId` = '" . $payOrderId . "' AND `created_at` > date_sub(now(), interval 1 minute);";
        $result = $connection->fetchAll($select);

        $sql = "INSERT INTO `" . $tableName . "` (`payOrderId`) Values ('" . $payOrderId . "') ON DUPLICATE KEY UPDATE `created_at` = now()";
        $connection->query($sql);

        return is_array($result) ? $result : array();
    }

    public static function removeProcessing($payOrderId)
    {
        $objectManager = self::getObjectManager();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('pay_processing');   
        $sql = "DELETE FROM `" . $tableName . "` WHERE `payOrderId` = '" . $payOrderId . "';"; 
        $connection->query($sql);      
    }

    public function getClientIp()
    {
        $ipforward = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        return !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ipforward;
    }
}
