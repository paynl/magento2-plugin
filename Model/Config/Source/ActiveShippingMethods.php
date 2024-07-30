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
     *
     * @return array
     */
    public function getShippingMethods()
    {
        $methods = [];
        $activeCarriers = $this->shipconfig->getActiveCarriers();
        foreach ($activeCarriers as $carrierCode => $carrierModel) {

            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    $code = $carrierCode . '_' . $methodCode;
                }
                $carrierTitle = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/title');
                $carrierName = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/name');
            }

            $methods[$code] = '[' . $carrierTitle . '] ' . $carrierName;
        }

        return $methods;
    }
}
