define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';

        var defaultComponent = 'Paynl_Payment/js/view/payment/method-renderer/default';

        var methods = [
            {type: 'paynl_payment_afterpay', component: defaultComponent},
            {type: 'paynl_payment_afterpay_international', component: defaultComponent},
            {type: 'paynl_payment_alipay', component: defaultComponent},
            {type: 'paynl_payment_alipayplus', component: defaultComponent},
            {type: 'paynl_payment_alma', component: defaultComponent},
            {type: 'paynl_payment_amazonpay', component: defaultComponent},
            {type: 'paynl_payment_amex', component: defaultComponent},
            {type: 'paynl_payment_bataviacadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_bbqcadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_beautycadeau', component: defaultComponent},
            {type: 'paynl_payment_beautyenmorecadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_biercheque', component: defaultComponent},
            {type: 'paynl_payment_biller', component: defaultComponent},
            {type: 'paynl_payment_billink', component: defaultComponent},
            {type: 'paynl_payment_bioscoopbon', component: defaultComponent},
            {type: 'paynl_payment_bizum', component: defaultComponent},
            {type: 'paynl_payment_blik', component: defaultComponent},
            {type: 'paynl_payment_bloemencadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_boekenbon', component: defaultComponent},
            {type: 'paynl_payment_brite', component: defaultComponent},
            {type: 'paynl_payment_capayable_gespreid', component: defaultComponent},
            {type: 'paynl_payment_cartebleue', component: defaultComponent},
            {type: 'paynl_payment_cashly', component: defaultComponent},
            {type: 'paynl_payment_creditclick', component: defaultComponent},
            {type: 'paynl_payment_cult', component: defaultComponent},
            {type: 'paynl_payment_dankort', component: defaultComponent},
            {type: 'paynl_payment_decadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_dinerbon', component: defaultComponent},
            {type: 'paynl_payment_eps', component: defaultComponent},
            {type: 'paynl_payment_fashioncheque', component: defaultComponent},
            {type: 'paynl_payment_fashiongiftcard', component: defaultComponent},
            {type: 'paynl_payment_festivalcadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_flyingblueplus', component: defaultComponent},
            {type: 'paynl_payment_gezondheidsbon', component: defaultComponent},
            {type: 'paynl_payment_giropay', component: defaultComponent},
            {type: 'paynl_payment_givacard', component: defaultComponent},
            {type: 'paynl_payment_good4fun', component: defaultComponent},
            {type: 'paynl_payment_googlepay', component: defaultComponent},
            {type: 'paynl_payment_horsesandgifts', component: defaultComponent},
            {type: 'paynl_payment_huisdierencadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_huisentuincadeau', component: defaultComponent},
            {type: 'paynl_payment_ideal', component: defaultComponent},
            {type: 'paynl_payment_in3business', component: defaultComponent},
            {type: 'paynl_payment_incasso', component: defaultComponent},
            {type: 'paynl_payment_instore', component: defaultComponent},
            {type: 'paynl_payment_kidsorteen', component: defaultComponent},
            {type: 'paynl_payment_klarna', component: defaultComponent},
            {type: 'paynl_payment_klarnakp', component: defaultComponent},
            {type: 'paynl_payment_kunstencultuurkaart', component: defaultComponent},
            {type: 'paynl_payment_maestro', component: defaultComponent},
            {type: 'paynl_payment_mastercard', component: defaultComponent},
            {type: 'paynl_payment_mbway', component: defaultComponent},
            {type: 'paynl_payment_mistercash', component: defaultComponent},
            {type: 'paynl_payment_mobilepay', component: defaultComponent},
            {type: 'paynl_payment_monizze', component: defaultComponent},
            {type: 'paynl_payment_mooigiftcard', component: defaultComponent},
            {type: 'paynl_payment_multibanco', component: defaultComponent},
            {type: 'paynl_payment_nationaletuinbon', component: defaultComponent},
            {type: 'paynl_payment_nexi', component: defaultComponent},
            {type: 'paynl_payment_overboeking', component: defaultComponent},
            {type: 'paynl_payment_onlinebankbetaling', component: defaultComponent},
            {type: 'paynl_payment_parfumcadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_payconiq', component: defaultComponent},
            {type: 'paynl_payment_paylink', component: defaultComponent},
            {type: 'paynl_payment_paypal', component: defaultComponent},
            {type: 'paynl_payment_paysafecard', component: defaultComponent},
            {type: 'paynl_payment_podiumcadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_postepay', component: defaultComponent},
            {type: 'paynl_payment_prontowonen', component: defaultComponent},
            {type: 'paynl_payment_przelewy24', component: defaultComponent},
            {type: 'paynl_payment_rotterdamcitycard', component: defaultComponent},
            {type: 'paynl_payment_rvrpas', component: defaultComponent},
            {type: 'paynl_payment_satispay', component: defaultComponent},
            {type: 'paynl_payment_saunaenwellnesscadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_shoesandsneakers', component: defaultComponent},
            {type: 'paynl_payment_sodexo', component: defaultComponent},
            {type: 'paynl_payment_sofortbanking', component: defaultComponent},
            {type: 'paynl_payment_sofortbanking_hr', component: defaultComponent},
            {type: 'paynl_payment_sofortbanking_ds', component: defaultComponent},
            {type: 'paynl_payment_spraypay', component: defaultComponent},
            {type: 'paynl_payment_stadspasamsterdam', component: defaultComponent},
            {type: 'paynl_payment_telefonischbetalen', component: defaultComponent},
            {type: 'paynl_payment_trustly', component: defaultComponent},
            {type: 'paynl_payment_upas', component: defaultComponent},
            {type: 'paynl_payment_vipps', component: defaultComponent},
            {type: 'paynl_payment_visa', component: defaultComponent},
            {type: 'paynl_payment_visamastercard', component: defaultComponent},
            {type: 'paynl_payment_vvvgiftcard', component: defaultComponent},
            {type: 'paynl_payment_webshopgiftcard', component: defaultComponent},
            {type: 'paynl_payment_wechatpay', component: defaultComponent},
            {type: 'paynl_payment_wero', component: defaultComponent},
            {type: 'paynl_payment_wijncadeau', component: defaultComponent},
            {type: 'paynl_payment_winkelcheque', component: defaultComponent},
            {type: 'paynl_payment_xafaxmynetpay', component: defaultComponent},
            {type: 'paynl_payment_yourgift', component: defaultComponent},
            {type: 'paynl_payment_yourgreengift', component: defaultComponent},
        ];

        function isApplePayAvailable()
        {
            try {
                return window.ApplePaySession;
            } catch (e) {
                console.warn(e);
                return false;
            }
        }

        if (isApplePayAvailable()) {
            methods.push({
                type: 'paynl_payment_applepay',
                component: defaultComponent
            });
        }

        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);
