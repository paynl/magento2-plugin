/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order'
    ],
    function ($, Component, url, placeOrderAction) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paynl_Payment/payment/billink'
            },
            kvknummer: null,
            billink_agree: null,
            showKVK: function () {
                return this.getKVK() > 0;
            },
            getKVK: function () {
                return window.checkoutConfig.payment.showkvk[this.item.method];
            },
            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        "kvknummer": this.kvknummer,
                        "billink_agree": this.billink_agree
                    }
                };
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getPaymentIcon: function () {
                return window.checkoutConfig.payment.icon[this.item.method];
            },
            placeOrder: function (data, event) {
                var placeOrder;
                var showingKVK = this.getKVK() == 2;

                if (showingKVK) {
                    if (this.billink_agree != true) {
                        alert('U dient eerst akkoord te gaan met de betalingsvoorwaarden van Billink.');
                        return false;
                    }
                    if (this.kvknummer == null || this.kvknummer.length < 8) {
                        alert('Voer een geldig KVK nummer in.');
                        return false;
                    }
                }

                if (event) {
                    event.preventDefault();
                }

                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);

                $.when(placeOrder).fail(function () {
                    this.isPlaceOrderActionAllowed(true);
                }.bind(this)).done(this.afterPlaceOrder.bind(this));

                return true;
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('/paynl/checkout/redirect?nocache=' + (new Date().getTime())));
            },
        });
    }
);