/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Paynl_Payment/js/view/payment/method-renderer/default',
        'mage/url'    
    ],
    function ($, Component, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paynl_Payment/payment/ideal'
            },
            selectedBank: null,
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