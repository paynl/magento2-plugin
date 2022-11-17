<?php

namespace Paynl\Payment\Helper;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use \Paynl\Payment\Model\Config\Source\LogOptions;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\HTTP\Header;


class PayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PAY_LOG_PREFIX = 'PAY.: ';

    private static $objectManager;
    private static $store;
    private $resource;
    private $remoteAddress;
    private $httpHeader;

    public function __construct(
        ResourceConnection $resource,
        RemoteAddress $remoteAddress,
        Header $httpHeader
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->httpHeader = $httpHeader;
        $this->resource = $resource;
    }

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

    /**
     * Checks if new-ppt is already processing, mark as processing if not marked already
     *
     * @param $payOrderId
     * @return bool
     */
    public function checkProcessing($payOrderId)
    {
        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('pay_processing');

            $select = $connection->select()->from([$tableName])->where('payOrderId = ?', $payOrderId)->where('created_at > date_sub(now(), interval 1 minute)');
            $result = $connection->fetchAll($select);

            $processing = !empty($result[0]);
            if (!$processing) {
                $connection->insertOnDuplicate(
                    $tableName,
                    ['payOrderId' => $payOrderId],
                    ['payOrderId', 'created_at']
                );
            }
        } catch (\Exception $e) {
            $processing = false;
        }
        return $processing;
    }

    /**
     * Removes processing mark after new-ppt is finished
     *
     * @param $payOrderId
     */
    public function removeProcessing($payOrderId)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('pay_processing');
        $connection->delete(
            $tableName,
            ['payOrderId = ?' => $payOrderId]
        );
    }

    public function getClientIp()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    public function getHttpUserAgent()
    {
        return $this->httpHeader->getHttpUserAgent();
    }

    /**
     * @param $field
     * @param $name
     * @param $errorCode
     * @param null $desc
     * @throws Exception
     */
    public function checkEmpty($field, $name, $errorCode, $desc = null)
    {
        if (empty($field)) {
            $desc = empty($desc) ? $name . ' is empty' : $desc;
            throw new Exception('Finish: ' . $desc, $errorCode);
        }
    }
}
