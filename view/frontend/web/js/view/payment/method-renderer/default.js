/*browser:true*/
/*global define*/
define(
     [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, additionalValidators) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paynl_Payment/payment/default'
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getPaymentIcon: function(){
                return window.checkoutConfig.payment.icon[this.item.method];
            },
            afterPlaceOrder: function () {
                $.mage.redirect('/paynl/checkout/redirect');
            },
        });
    }
);