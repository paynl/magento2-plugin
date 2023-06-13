<?php

namespace Paynl\Payment\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Store\Model\Store;
use Paynl\Payment\Logging\Logger;
use Paynl\Payment\Model\Config\Source\LogOptions;

class PayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const PAY_LOG_PREFIX = 'PAY.: ';

    private $store;
    private $resource;
    private $remoteAddress;
    private $httpHeader;
    private $logger;
    private $cookieManager;
    private $cookieMetadataFactory;

    /**
     * @param ResourceConnection $resource
     * @param RemoteAddress $remoteAddress
     * @param Header $httpHeader
     * @param Store $store
     * @param Logger $logger
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        ResourceConnection $resource,
        RemoteAddress $remoteAddress,
        Header $httpHeader,
        Store $store,
        Logger $logger,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->httpHeader = $httpHeader;
        $this->resource = $resource;
        $this->store = $store;
        $this->logger = $logger;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @param integer $level
     * @param string $type
     * @return boolean
     */
    public function hasCorrectLevel($level, $type)
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
    public function logCritical($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        $this->writeLog($text, 'critical', $params, $store);
    }

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store  $store
     * @return void
     */
    public function logNotice($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        $this->writeLog($text, 'notice', $params, $store);
    }

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store  $store
     * @return void
     */
    public function logInfo($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        $this->writeLog($text, 'info', $params, $store);
    }

    /**
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store|null $store
     * @return void
     */
    public function logDebug($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        $this->writeLog($text, 'debug', $params, $store);
    }

    /**
     * Logs while bypassing the loglevel setting.
     *
     * @param string $text
     * @param array $params
     * @param \Magento\Store\Model\Store |null $store
     * @return void
     */
    public function log($text, array $params = array(), \Magento\Store\Model\Store $store = null)
    {
        $this->logger->notice($text, $params);
    }

    /**
     * @param string $text
     * @param string $type
     * @param array $params
     * @param \Magento\Store\Model\Store|null $store
     * @return void
     */
    public function writeLog($text, $type, array $params, \Magento\Store\Model\Store $store = null)
    {
        $level = $this->store->getConfig('payment/paynl/logging_level');
        if (self::hasCorrectLevel($level, $type)) {
            if (!is_array($params)) {
                $params = array();
            }
            switch ($type) {
                case 'critical':
                    $this->logger->critical($text, $params);
                    break;
                case 'notice':
                    $this->logger->notice($text, $params);
                    break;
                case 'info':
                    $this->logger->info($text, $params);
                    break;
                case 'debug':
                    $this->logger->debug($text, $params);
                    break;
            }
        }
    }

    /**
     * @param string $cookieName
     * @param string $value
     * @return void
     */
    public function setCookie($cookieName, $value)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(300)
            ->setSecure(false)
            ->setPath('/')
            ->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(
            $cookieName,
            $value,
            $metadata
        );
    }

    /**
     * @param string $cookieName
     * @return mixed
     */
    public function getCookie($cookieName)
    {
        return $this->cookieManager->getCookie($cookieName);
    }

    /**
     * @param string $cookieName
     * @phpcs:disable PSR12.Functions.ReturnTypeDeclaration
     * @phpcs:disable PEAR.Commenting.FunctionComment.MissingReturn
     * @return void|mixed
     */
    public function deleteCookie($cookieName)
    {
        if ($this->cookieManager->getCookie($cookieName)) {
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            $metadata->setPath('/');
            return $this->cookieManager->deleteCookie($cookieName, $metadata); // phpcs:ignore
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

    /**
     * @param string $phone
     * @return string|null
     */
    public static function validatePhoneNumber($phone)
    {
        if (!empty($phone)) {
            $phone = trim($phone);
            $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
            $valid_number = preg_match('/^(\+\s*)?(?=([.,\s()-]*\d){5})([\d(][\d.,\s()-]*)([[:alpha:]#][^\d]*\d.*)?$/', $phone, $matches) && preg_match('/\d{2}/', $phone);
            if ($valid_number) {
                return trim($matches[1]) . trim($matches[3]) . (!empty($matches[4]) ? ' ' . $matches[4] : '');
            }
        }
        return null;
    }
}
