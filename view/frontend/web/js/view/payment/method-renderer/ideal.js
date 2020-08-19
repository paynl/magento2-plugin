/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Paynl_Payment/js/view/payment/method-renderer/default',
        'mage/url', 
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component, url, quote) {
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
            showforCompany: function(){              
                if(this.getforCompany() == null || this.getforCompany().length == 0 || this.getforCompany() == 0){
                    return true;
                }
                if(this.getforCompany() == 1 && this.getCompany().length == 0){
                    return true;
                }
                if(this.getforCompany() == 2 && this.getCompany().length > 0){
                    return true;
                }
                return false;
            }, 
            getCompany: function () {                
                if (typeof quote.billingAddress._latestValue.company !== 'undefined') {
                    return quote.billingAddress._latestValue.company;
                }
                return '';                
            },   
            getforCompany   : function () {
                return window.checkoutConfig.payment.showforcompany[this.item.method];
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