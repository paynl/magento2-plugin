<?php

namespace Paynl\Payment\Test\Unit\Model\Paymentmethod;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\Paylink;

class PaylinkTestDouble extends Paylink
{
    /** @var object */
    public $payOrder;

    /** @var InfoInterface */
    public $paymentInfo;

    /**
     * @param OrderRepository $orderRepository
     * @param Config $paynlConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Resolver $locale
     * @param PaymentData $paymentData
     * @param Renderer $addressRenderer
     * @param TransportBuilder $transportBuilder
     * @param PayHelper $payHelper
     * @param OrderConfig $orderConfig
     */
    public function __construct(
        OrderRepository $orderRepository,
        Config $paynlConfig,
        ScopeConfigInterface $scopeConfig,
        Resolver $locale,
        PaymentData $paymentData,
        Renderer $addressRenderer,
        TransportBuilder $transportBuilder,
        PayHelper $payHelper,
        OrderConfig $orderConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->paynlConfig = $paynlConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->getLocale = $locale;
        $this->paymentData = $paymentData;
        $this->addressRenderer = $addressRenderer;
        $this->transportBuilder = $transportBuilder;
        $this->payHelper = $payHelper;
        $this->orderConfig = $orderConfig;
    }

    /**
     * @return InfoInterface
     */
    public function getInfoInstance()
    {
        return $this->paymentInfo;
    }

    /**
     * @param string $field
     * @param integer|null $storeId
     * @return string
     */
    public function getConfigData($field, $storeId = null)
    {
        return 'processing';
    }

    /**
     * @param Order $order
     * @return object
     */
    public function createPayOrder(Order $order)
    {
        return $this->payOrder;
    }
}
