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
        'paynl_payment_bataviacadeaukaart',
        'paynl_payment_beautycadeau',
        'paynl_payment_biercheque',
        'paynl_payment_biller',
        'paynl_payment_billink',
        'paynl_payment_bioscoopbon',
        'paynl_payment_blik',
        'paynl_payment_bloemencadeaukaart',
        'paynl_payment_boekenbon',
        'paynl_payment_capayable_gespreid',
        'paynl_payment_cartebleue',
        'paynl_payment_cashly',
        'paynl_payment_creditclick',
        'paynl_payment_cult',
        'paynl_payment_dankort',
        'paynl_payment_decadeaukaart',
        'paynl_payment_dinerbon',
        'paynl_payment_eps',
        'paynl_payment_fashioncheque',
        'paynl_payment_fashiongiftcard',
        'paynl_payment_festivalcadeaukaart',
        'paynl_payment_gezondheidsbon',
        'paynl_payment_giropay',
        'paynl_payment_givacard',
        'paynl_payment_good4fun',
        'paynl_payment_googlepay',
        'paynl_payment_horsesandgifts',
        'paynl_payment_huisentuincadeau',
        'paynl_payment_ideal',
        'paynl_payment_in3business',
        'paynl_payment_incasso',
        'paynl_payment_instore',
        'paynl_payment_klarna',
        'paynl_payment_klarnakp',
        'paynl_payment_maestro',
        'paynl_payment_mistercash',
        'paynl_payment_monizze',
        'paynl_payment_mooigiftcard',
        'paynl_payment_multibanco',
        'paynl_payment_nexi',
        'paynl_payment_overboeking',
        'paynl_payment_onlinebankbetaling',
        'paynl_payment_parfumcadeaukaart',
        'paynl_payment_payconiq',
        'paynl_payment_paypal',
        'paynl_payment_paysafecard',
        'paynl_payment_podiumcadeaukaart',
        'paynl_payment_postepay',
        'paynl_payment_przelewy24',
        'paynl_payment_prontowonen',
        'paynl_payment_shoesandsneakers',
        'paynl_payment_sodexo',
        'paynl_payment_sofortbanking',
        'paynl_payment_sofortbanking_hr',
        'paynl_payment_sofortbanking_ds',
        'paynl_payment_spraypay',
        'paynl_payment_telefonischbetalen',
        'paynl_payment_trustly',
        'paynl_payment_visamastercard',
        'paynl_payment_vvvgiftcard',
        'paynl_payment_webshopgiftcard',
        'paynl_payment_wechatpay',
        'paynl_payment_wijncadeau',
        'paynl_payment_winkelcheque',
        'paynl_payment_yourgift',
        'paynl_payment_yourgreengift'
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
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['paymentoptions'][$code]        = $this->getPaymentOptions($code);
                $config['payment']['showpaymentoptions'][$code]    = $this->showPaymentOptions($code);
                $config['payment']['defaultpaymentoption'][$code]  = $this->getDefaultPaymentOption($code);
                $config['payment']['hidepaymentoptions'][$code]    = $this->hidePaymentOptions($code);
                $config['payment']['icon'][$code]         = $this->getIcon($code);
                $config['payment']['showcompanyfield'][$code] = $this->getCompanyField($code);
                $config['payment']['showkvk'][$code]      = $this->getKVK($code);
                $config['payment']['showvat'][$code]      = $this->getVAT($code);
                $config['payment']['showdob'][$code]      = $this->getDOB($code);
                $config['payment']['showforcompany'][$code] = $this->getCompany($code);
                $config['payment']['showforgroup'][$code] = $this->getCustomerGroup($code);

                $config['payment']['disallowedshipping'][$code] = $this->getDisallowedShippingMethods($code);
                $config['payment']['currentipisvalid'][$code]    = $this->methods[$code]->isCurrentIpValid();
                $config['payment']['currentagentisvalid'][$code] = $this->methods[$code]->isCurrentAgentValid();
                $config['payment']['defaultpaymentmethod'][$code] = $this->methods[$code]->isDefaultPaymentOption();
            }
        }

        $config['payment']['useAdditionalValidation'] = $this->paynlConfig->getUseAdditionalValidation();

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

    /**
     * @param string $code
     * @return string
     */
    protected function getPaymentOptions($code)
    {
        return $this->methods[$code]->getPaymentOptions();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function showPaymentOptions($code)
    {
        return $this->methods[$code]->showPaymentOptions();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getDefaultPaymentOption($code)
    {
        return $this->methods[$code]->getDefaultPaymentOption();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function hidePaymentOptions($code)
    {
        return $this->methods[$code]->hidePaymentOptions();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getCompanyField($code)
    {
        return $this->methods[$code]->getCompanyField();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getKVK($code)
    {
        return $this->methods[$code]->getKVK();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getVAT($code)
    {
        return $this->methods[$code]->getVAT();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getDOB($code)
    {
        return $this->methods[$code]->getDOB();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getDisallowedShippingMethods($code)
    {
        return $this->methods[$code]->getDisallowedShippingMethods();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getCompany($code)
    {
        return $this->methods[$code]->getCompany();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getCustomerGroup($code)
    {
        return $this->methods[$code]->getCustomerGroup();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getIcon($code)
    {
        return $this->paynlConfig->getIconUrl($code);
    }
}
