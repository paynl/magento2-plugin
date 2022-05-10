<?php
namespace Paynl\Payment\Model\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Json\Helper\Data;
use Paynl\Payment;
use Paynl\Encryption;
use Paynl\Error\Api;
use Paynl\Error\Error;
use Paynl\Error\Required\ApiToken;
use Paynl\Payment\Model\Config;

class PublicKeysHelper
{
    const CACHE_KEY = 'paynl_public_encryption_keys';
    const CACHE_TTL = 15768000;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * PublicKeysHelper constructor.
     * @param Config $config
     * @param CacheInterface $cache
     * @param Data $jsonHelper
     */
    public function __construct(
        Config $config,
        CacheInterface $cache,
        Data $jsonHelper
    )
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @return mixed|string
     * @throws Api
     * @throws Error
     * @throws ApiToken
     */
    public function getKeys()
    {
        $keysJson = $this->cache->load(self::CACHE_KEY);

        if (!empty($keysJson))
        {
            $keysJson = $this->jsonHelper->jsonDecode($keysJson);
        }
        else
        {
            //$keysJson = Creditcard::publicKeys();
            /*    ssed to Braintree\Gateway). [] []
            [2021-11-23 15:37:51] main.CRITICAL:

                Warning: count(): Parameter must be an array or an object that implements Countable

                in /src/public/vendor/paynl/magento2-plugin/Model/Helper/PublicKeysHelper.php on line 68

                {"exception":"[object] (Exception(code: 0): Warning: count(): Parameter must be an array or an object that implements Countable in /s
                rc/public/vendor/paynl/magento2-plugin/Model/Helper/PublicKeysHelper.php on line 68 at /src/public/vendor/magento/framework/App/ErrorHandler.php:61)"} []
    */


            $newKeys = Payment::paymentEncryptionKeys()->getKeys();

            #        $this->logger->critical(print_r($result, true), []) ;

            if (!empty($newKeys) && count($newKeys) > 0)
            {
                $this->cache->save(
                    $this->jsonHelper->jsonEncode($newKeys),
                    self::CACHE_KEY,
                    ['paynl', 'paynl_encryption'],
                    self::CACHE_TTL
                );
            }

            $keysJson = $newKeys;
        }

        return $keysJson;
    }
}
