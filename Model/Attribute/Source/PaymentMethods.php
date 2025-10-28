<?php
declare(strict_types=1);

namespace Paynl\Payment\Model\Attribute\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use \Paynl\Payment\Model\Config;

class PaymentMethods extends AbstractSource
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param Config $config
     */
    public function __construct(
        RequestInterface     $request,
        ScopeConfigInterface $scopeConfigInterface,
        Config               $config
    )
    {
        $this->request = $request;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->config = $config;
    }

    public function getAllOptions(): array
    {
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        $scope = 'default';
        $scopeId = 0;

        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        } elseif ($websiteId) {
            $scope = 'websites';
            $scopeId = $websiteId;
        }

        $configPath = 'payment/paynl/product_exclusive';
        $methodsString = (string) $this->scopeConfigInterface->getValue($configPath, $scope, $scopeId);

        $methods = $methodCodes = array_filter(explode(',', $methodsString));
        $paynlMethods = [];

        foreach ($methods as $method) {
            $title = $this->scopeConfigInterface->getValue('payment/' . $method . '/title', $scope, $scopeId);
            $paynlMethods[] = [
                'label' => $title ?: $method,
                'value' => $method,
            ];
        }

        return $paynlMethods;
    }

}
