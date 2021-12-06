<?php

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
        'paynl_payment_afterpay_international',
        'paynl_payment_alipay',
        'paynl_payment_amazonpay',
        'paynl_payment_amex',
        'paynl_payment_applepay',
        'paynl_payment_biercheque',
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
        'paynl_payment_good4fun',
        'paynl_payment_googlepay',
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
        'paynl_payment_sofortbanking_hr',
        'paynl_payment_sofortbanking_ds',
        'paynl_payment_spraypay',
        'paynl_payment_telefonischbetalen',
        'paynl_payment_tikkie',
        'paynl_payment_trustly',
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
     * ConfigProvider constructor.
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param Config $paynlConfig
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        Config $paynlConfig
    ) {
        $this->paynlConfig = $paynlConfig;
        $this->escaper = $escaper;
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
                $config['payment']['paymentoptions'][$code]        = $this->getPaymentOptions($code);
                $config['payment']['defaultpaymentoption'][$code]  = $this->getDefaultPaymentOption($code);
                $config['payment']['hidepaymentoptions'][$code]    = $this->hidePaymentOptions($code);
                $config['payment']['icon'][$code]         = $this->getIcon($code);
                $config['payment']['showkvk'][$code]      = $this->getKVK($code);
                $config['payment']['showvat'][$code]      = $this->getVAT($code);
                $config['payment']['showdob'][$code]      = $this->getDOB($code);
                $config['payment']['showforcompany'][$code] = $this->getCompany($code);

                $config['payment']['disallowedshipping'][$code] = $this->getDisallowedShippingMethods($code);
                $config['payment']['currentipisvalid'][$code]    = $this->methods[$code]->isCurrentIpValid();
                $config['payment']['currentagentisvalid'][$code] = $this->methods[$code]->isCurrentAgentValid();
                $config['payment']['defaultpaymentmethod'][$code] = $this->methods[$code]->isDefaultPaymentOption();
            }
        }

        $config['payment']['useAdditionalValidation'] = $this->paynlConfig->getUseAdditionalValidation();
        $config['payment']['iconsize']                = $this->paynlConfig->getIconSize();
        ;

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

    protected function getPaymentOptions($code)
    {
        return $this->methods[$code]->getPaymentOptions();
    }

    protected function getDefaultPaymentOption($code)
    {
        return $this->methods[$code]->getDefaultPaymentOption();
    }

    protected function hidePaymentOptions($code)
    {
        return $this->methods[$code]->hidePaymentOptions();
    }

    protected function getKVK($code)
    {
        return $this->methods[$code]->getKVK();
    }

    protected function getVAT($code)
    {
        return $this->methods[$code]->getVAT();
    }

    protected function getDOB($code)
    {
        return $this->methods[$code]->getDOB();
    }

    protected function getDisallowedShippingMethods($code)
    {
        return $this->methods[$code]->getDisallowedShippingMethods();
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
        $url = $this->paynlConfig->getIconUrl($code, $this->methods[$code]->getPaymentOptionId());
        return $url;
    }
}
