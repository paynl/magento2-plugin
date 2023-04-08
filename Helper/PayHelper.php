<?php

namespace Paynl\Payment\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Paynl\Payment\Model\Config\Source\LogOptions;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\HTTP\Header;

class PayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const PAY_LOG_PREFIX = 'PAY.: ';

    private static $objectManager;
    private static $store;
    private $resource;
    private $remoteAddress;
    private $httpHeader;

    /**
     * @param ResourceConnection $resource
     * @param RemoteAddress $remoteAddress
     * @param Header $httpHeader
     */
    public function __construct(ResourceConnection $resource, RemoteAddress $remoteAddress, Header $httpHeader)
    {
        $this->remoteAddress = $remoteAddress;
        $this->httpHeader = $httpHeader;
        $this->resource = $resource;
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public static function getObjectManager()
    {
        if (empty(self::$objectManager)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            self::$objectManager = $objectManager;
        }
        return self::$objectManager;
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return mixed
     */
    public static function getStore(\Magento\Store\Model\Store $store)
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

    /**
     * @param integer $level
     * @param string $type
     * @return boolean
     */
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

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store  $store
     * @return void
     */
    public static function logCritical($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        self::writeLog($text, 'critical', $params, $store);
    }

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store  $store
     * @return void
     */
    public static function logNotice($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        self::writeLog($text, 'notice', $params, $store);
    }

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store  $store
     * @return void
     */
    public static function logInfo($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        self::writeLog($text, 'info', $params, $store);
    }

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store  $store
     * @return void
     */
    public static function logDebug($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        self::writeLog($text, 'debug', $params, $store);
    }

    /**
     * Logs while bypassing the loglevel setting.
     *
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store |null $store
     * @return void
     */
    public static function log($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        $objectManager = self::getObjectManager();
        $logger = $objectManager->get(\Paynl\Payment\Logging\Logger::class);
        $logger->notice($text, $params);
    }

    /**
     * @param string $text
     * @param string $type
     * @param array $params
     * @param \Magento\Store\Model\Store $store
     * @return void
     */
    public static function writeLog($text, $type, array $params, \Magento\Store\Model\Store $store)
    {
        $store = self::getStore($store);
        $level = $store->getConfig('payment/paynl/logging_level');

        if (self::hasCorrectLevel($level, $type)) {
            if (!is_array($params)) {
                $params = array();
            }
            $objectManager = self::getObjectManager();
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

    /**
     * @param string $cookieName
     * @param string $value
     * @return void
     */
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

    /**
     * @param string $cookieName
     * @return mixed
     */
    public static function getCookie($cookieName)
    {
        $objectManager = self::getObjectManager();
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        return $cookieManager->getCookie($cookieName);
    }

    /**
     * @param string $cookieName
     * @return void
     * // phpcs:ignore
     */
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
     * @param string $payOrderId
     * @return boolean
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
     * @param string $payOrderId
     * @return void
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

    /**
     * @return false|string
     */
    public function getClientIp()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    /**
     * @return string
     */
    public function getHttpUserAgent()
    {
        return $this->httpHeader->getHttpUserAgent();
    }

    /**
     * @param string $exceptionMessage
     * @return \Magento\Framework\Phrase
     */
    public static function getFriendlyMessage($exceptionMessage)
    {
        $exceptionMessage = strtolower(trim($exceptionMessage));

        if (stripos($exceptionMessage, 'minimum amount') !== false) {
            $strMessage = __('Unfortunately the order amount does not fit the requirements for this payment method.');
        } elseif (stripos($exceptionMessage, 'not enabled for this service') !== false) {
            $strMessage = __('The selected payment method is not enabled. Please select another payment method.');
        } else {
            $strMessage = __('Unfortunately something went wrong.');
        }

        return $strMessage;
    }

    /**
     * @param string $gender
     * @return string|null
     */
    public static function genderConversion($gender)
    {
        switch ($gender) {
            case '1':
                $gender = 'M';
                break;
            case '2':
                $gender = 'F';
                break;
            default:
                $gender = null;
                break;
        }
        return $gender;
    }
}
