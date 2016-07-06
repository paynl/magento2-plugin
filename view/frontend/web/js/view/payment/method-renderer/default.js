/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function ($, Component, url) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'Paynl_Payment/payment/default'
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getPaymentIcon: function () {
                return window.checkoutConfig.payment.icon[this.item.method];
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('/paynl/checkout/redirect'));
            },

        });
    }
);