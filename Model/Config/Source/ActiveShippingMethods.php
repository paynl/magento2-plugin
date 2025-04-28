<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Shipping\Model\Config;

class ActiveShippingMethods implements ArrayInterface
{
    /**
     * @var Config
     */
    protected $shipconfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * constructor.
     * @param Config $shipconfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $shipconfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->shipconfig = $shipconfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptions = $this->getShippingMethods();

        $arrResult = [];
        foreach ($arrOptions as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }

    /**
     * Get options in "key-value" format
     * @return array
     */
    public function getShippingMethods(): array
    {
        $methods = [];
        $activeCarriers = $this->shipconfig->getActiveCarriers();

        foreach ($activeCarriers as $carrierCode => $carrierModel) {
            $allowedMethods = $carrierModel->getAllowedMethods();
            if (!$allowedMethods) {
                continue;
            }

            $carrierTitle = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/title');
            $carrierName = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/name');

            foreach ($allowedMethods as $methodCode => $method) {
                $code = $carrierCode . '_' . $methodCode;
                # Skip instore pickup methods
                if (in_array($code, ['instore_pickup', 'instore_instore'])) {
                    continue;
                }
                $methods[$code] = '[' . $carrierTitle . '] ' . $carrierName;
            }
        }

        return $methods;
    }
}
