<?php
namespace Paynl\Payment\Model\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Json\Helper\Data;
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

        if ($keysJson) {
            $keysJson = $this->jsonHelper->jsonDecode($keysJson);
        } else {
            $keysJson = Encryption::publicKeys();

            if (count($keysJson) > 0) {
                $this->cache->save(
                    $this->jsonHelper->jsonEncode($keysJson),
                    self::CACHE_KEY,
                    ['paynl', 'paynl_encryption'],
                    self::CACHE_TTL
                );
            }
        }

        return $keysJson;
    }
}
