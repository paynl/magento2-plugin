define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function ($,
              Component,
              rendererList) {
        'use strict';

        var defaultComponent = 'Paynl_Payment/js/view/payment/method-renderer/default';

        var methods = [
            {type: 'paynl_payment_afterpay', component: defaultComponent},
            {type: 'paynl_payment_billink', component: defaultComponent},
            {type: 'paynl_payment_capayable', component: defaultComponent},
            {type: 'paynl_payment_capayable_gespreid', component: defaultComponent},
            {type: 'paynl_payment_cartebleue', component: defaultComponent},
            {type: 'paynl_payment_clickandbuy', component: defaultComponent},
            {type: 'paynl_payment_fashioncheque', component: defaultComponent},
            {type: 'paynl_payment_fashiongiftcard', component: defaultComponent},
            {type: 'paynl_payment_gezondheidsbon', component: defaultComponent},
            {type: 'paynl_payment_giropay', component: defaultComponent},
            {type: 'paynl_payment_givacard', component: defaultComponent},
            {type: 'paynl_payment_ideal', component: defaultComponent},
            {type: 'paynl_payment_maestro', component: defaultComponent},
            {type: 'paynl_payment_mistercash', component: defaultComponent},
            {type: 'paynl_payment_mybank', component: defaultComponent},
            {type: 'paynl_payment_overboeking', component: defaultComponent},
            {type: 'paynl_payment_paypal', component: defaultComponent},
            {type: 'paynl_payment_paysafecard', component: defaultComponent},
            {type: 'paynl_payment_podiumcadeaukaart', component: defaultComponent},
            {type: 'paynl_payment_postepay', component: defaultComponent},
            {type: 'paynl_payment_sofortbanking', component: defaultComponent},
            {type: 'paynl_payment_telefonischbetalen', component: defaultComponent},
            {type: 'paynl_payment_visamastercard', component: defaultComponent},
            {type: 'paynl_payment_vvvgiftcard', component: defaultComponent},
            {type: 'paynl_payment_webshopgiftcard', component: defaultComponent},
            {type: 'paynl_payment_wijncadeau', component: defaultComponent},
            {type: 'paynl_payment_yourgift', component: defaultComponent}
        ];
        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);