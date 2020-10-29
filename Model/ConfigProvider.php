<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;


class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        'paynl_payment_afterpay',
        'paynl_payment_alipay',
        'paynl_payment_amex',
        'paynl_payment_applepay',
        'paynl_payment_billink',
        'paynl_payment_decadeaukaart',
        'paynl_payment_capayable',
        'paynl_payment_capayable_gespreid',
        'paynl_payment_cartasi',
        'paynl_payment_cartebleue',
        'paynl_payment_cashly',
        'paynl_payment_clickandbuy',
        'paynl_payment_creditclick',
        'paynl_payment_dankort',
        'paynl_payment_eps',
        'paynl_payment_fashioncheque',
        'paynl_payment_fashiongiftcard',
        'paynl_payment_focum',
        'paynl_payment_gezondheidsbon',
        'paynl_payment_giropay',
        'paynl_payment_givacard',
        'paynl_payment_huisentuincadeau',
        'paynl_payment_ideal',
        'paynl_payment_instore',
        'paynl_payment_klarna',
        'paynl_payment_klarnakp',
        'paynl_payment_maestro',
        'paynl_payment_mistercash',
        'paynl_payment_multibanco',
        'paynl_payment_mybank',
        'paynl_payment_overboeking',
        'paynl_payment_payconiq',
        'paynl_payment_paypal',
        'paynl_payment_paysafecard',
        'paynl_payment_podiumcadeaukaart',
        'paynl_payment_postepay',
        'paynl_payment_przelewy24',
        'paynl_payment_sofortbanking',
        'paynl_payment_spraypay',
        'paynl_payment_telefonischbetalen',
        'paynl_payment_tikkie',
        'paynl_payment_visamastercard',
        'paynl_payment_vvvgiftcard',
        'paynl_payment_webshopgiftcard',
        'paynl_payment_wechatpay',
        'paynl_payment_wijncadeau',
        'paynl_payment_yehhpay',
        'paynl_payment_yourgift'
    ];


    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Config
     */
    protected $paynlConfig;

    /**
     * @var \Magento\Payment\Model\Config|Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * ConfigProvider constructor.
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param Config $paynlConfig
     * @param Magento\Payment\Model\Config $paymentConfig
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        Config $paynlConfig,
        \Magento\Payment\Model\Config $paymentConfig
    ) {
        $this->paynlConfig = $paynlConfig;
        $this->escaper = $escaper;
        $this->paymentConfig = $paymentConfig;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['banks'][$code] = $this->getBanks($code);
                $config['payment']['public_encryption_keys'][$code] = $this->getPublicEncryptionKeys($code);
                $config['payment']['cse_enabled'][$code] = $this->getCseEnabled($code);
                $config['payment']['cse_modal_payment_complete'][$code] = $this->getModalEnabledForPaymentComplete($code);
                $config['payment']['cse_modal_payment_complete_redirection_timeout'][$code] = $this->getPaymentCompleteRedirectionTimeout($code);
                $config['payment']['cse_modal_payment_failure'][$code] = $this->getModalEnabledForPaymentFailure($code);
                $config['payment']['cc_months'][$code] = $this->getCcMonths();
                $config['payment']['cc_years'][$code] = $this->getCcYears();
                $config['payment']['icon'][$code] = $this->getIcon($code);
                $config['payment']['showkvk'][$code] = $this->getKVK($code);
                $config['payment']['showdob'][$code] = $this->getDOB($code);
                $config['payment']['showforcompany'][$code] = $this->getCompany($code);
            }
        }

        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     *
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    protected function getBanks($code)
    {
        return $this->methods[$code]->getBanks();
    }

    protected function getPublicEncryptionKeys($code)
    {
        return $this->methods[$code]->getPublicEncryptionKeys();
    }

    protected function getCseEnabled($code)
    {
        return $this->methods[$code]->getCseEnabled();
    }

    protected function getModalEnabledForPaymentComplete($code)
    {
        return $this->methods[$code]->getModalEnabledForPaymentComplete();
    }

    protected function getPaymentCompleteRedirectionTimeout($code)
    {
        return $this->methods[$code]->getPaymentCompleteRedirectionTimeout();
    }

    protected function getModalEnabledForPaymentFailure($code)
    {
        return $this->methods[$code]->getModalEnabledForPaymentFailure();
    }

    protected function getKVK($code)
    {
        return $this->methods[$code]->getKVK();
    }

    protected function getDOB($code)
    {
        return $this->methods[$code]->getDOB();
    }

    protected function getCompany($code)
    {
        return $this->methods[$code]->getCompany();
    }

    /**
     * Get payment method icon
     *
     * @param string $code
     *
     * @return string
     */
    protected function getIcon($code)
    {
        $url = $this->paynlConfig->getIconUrl();
        return str_replace('#paymentOptionId#', $this->methods[$code]->getPaymentOptionId(), $url);
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        return $this->paymentConfig->getMonths();
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        return $this->paymentConfig->getYears();
    }
}
