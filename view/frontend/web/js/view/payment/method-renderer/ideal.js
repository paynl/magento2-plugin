/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Paynl_Payment/js/view/payment/method-renderer/default'
    ],
    function ($, Component, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paynl_Payment/payment/ideal'
            },

            selectedBank: null,
            getBanksText: function(){
                return window.checkoutConfig.payment.bankstext[this.item.method];
            },
            getBanks: function(){
                return window.checkoutConfig.payment.banks[this.item.method];
            },
            showBanks: function(){
                return window.checkoutConfig.payment.banks[this.item.method].length > 0;
            },
            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        "bank_id": this.selectedBank
                    }
                };
            }
        });
    }
);