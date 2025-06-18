<?php

namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface as Scope;

class UpdateMulticoreConfig implements DataPatchInterface
{
    private $configWriter;
    private $scopeConfig;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
    }

    public function apply()
    {
        # Existing multicore settings
        $this->configWriter->save('payment/paynl/multicore', 'https://connect.pay.nl', Scope::SCOPE_TYPE_DEFAULT, 0);
        $this->configWriter->save('payment/paynl/cores', '', Scope::SCOPE_TYPE_DEFAULT, 0);

        # Generate process_secret when not exists
        $secret = $this->scopeConfig->getValue('payment/paynl/process_secret', Scope::SCOPE_TYPE_DEFAULT);

        if (empty($secret)) {
            $secret = bin2hex(random_bytes(32)); // 64 random chars
            $this->configWriter->save('payment/paynl/process_secret', $secret, Scope::SCOPE_TYPE_DEFAULT, 0);
        }
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
