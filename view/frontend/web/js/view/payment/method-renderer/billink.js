/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component, url, placeOrderAction, quote) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paynl_Payment/payment/billink'
            },
            kvknummer: null,
            dateofbirth: null,
            billink_agree: null,
            showKVK: function () {
                return this.getKVK() > 0;
            },
            getKVK: function () {
                return window.checkoutConfig.payment.showkvk[this.item.method];
            },
            showDOB: function () {
                return this.getDOB() > 0;
            },
            getDOB: function () {
                return window.checkoutConfig.payment.showdob[this.item.method];
            },
            showKVKDOB: function () {
                return this.getDOB() > 0;
            },
            getKVKDOB: function () {
                return (this.getDOB() > 0 && this.getKVK() > 0);
            },
            isVisible:function(){
                var disallowedShippingMethods = this.getDisallowedShipping().split(',');
                if($.isArray(disallowedShippingMethods) && disallowedShippingMethods.length > 0){
                    var carrier_code = typeof quote.shippingMethod().carrier_code !== 'undefined' ? quote.shippingMethod().carrier_code + '_' : '';
                    var method_code = typeof quote.shippingMethod().method_code !== 'undefined' ? quote.shippingMethod().method_code : '';
                    var currentShippingMethod = carrier_code + method_code;                  
                    if(disallowedShippingMethods.includes(currentShippingMethod) && currentShippingMethod.length > 0){
                        return false;
                    }
                }        
                if(this.getforCompany() == 1 && this.getCompany().length != 0){
                    return false;
                }
                if(this.getforCompany() == 2 && this.getCompany().length == 0){
                    return false;
                }
                return true;
            },
            getDisallowedShipping: function () {
                return window.checkoutConfig.payment.disallowedshipping[this.item.method];
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

                var dob = new Date(this.dateofbirth);

                var dd = dob.getDate();
                var mm = dob.getMonth() + 1;

                var yyyy = dob.getFullYear();
                if (dd < 10) {
                    dd = '0' + dd;
                }
                if (mm < 10) {
                    mm = '0' + mm;
                }
                var dob_format = dd + '-' + mm + '-' + yyyy;

                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        "kvknummer": this.kvknummer,
                        "dob": dob_format,
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
                var showingDOB = this.getDOB() == 2;
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
                if (showingDOB) {
                    if (this.dateofbirth == null || this.dateofbirth.length < 1) {
                        alert('Voer een geldig geboortedatum in.');
                        return false;
                    }
                }

                if (event) {
                    event.preventDefault();
                }

                $('#billink-button').html('Processing').attr('disabled','disabled');

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
