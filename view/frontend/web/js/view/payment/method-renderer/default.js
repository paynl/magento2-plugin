/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, Component, url, placeOrderAction, quote, alert) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Paynl_Payment/payment/default'
            },
            selectedBank: null,
            kvknummer: null,
            dateofbirth: null,
            billink_agree: null,
            isVisible:function(){
                var disallowedShippingMethods = this.getDisallowedShipping();
                if (disallowedShippingMethods) {
                    var carrier_code = typeof quote.shippingMethod().carrier_code !== 'undefined' ? quote.shippingMethod().carrier_code + '_' : '';
                    var method_code = typeof quote.shippingMethod().method_code !== 'undefined' ? quote.shippingMethod().method_code : '';
                    var currentShippingMethod = carrier_code + method_code;
                    var disallowedShippingMethodsSplitted = disallowedShippingMethods.split(',');
                    if (disallowedShippingMethodsSplitted.includes(currentShippingMethod) && currentShippingMethod.length > 0) {
                        return false;
                    }
                }
                if(this.getforCompany() == 1 && this.getCompany().length != 0){
                    return false;
                }
                if(this.getforCompany() == 2 && this.getCompany().length == 0){
                    return false;
                }
                if(!this.isExclusiveForCurrentIP()){
                    return false;
                }
                if(!this.isExclusiveForCurrentUseragent()){
                    return false;
                }
                return true;
            },
            isExclusiveForCurrentIP: function () {
                return window.checkoutConfig.payment.exclusiveforipaddress[this.item.method];
            },  
            isExclusiveForCurrentUseragent: function () {
                return window.checkoutConfig.payment.exclusiveforuseragent[this.item.method];
            },  
            getDisallowedShipping: function () {
                return window.checkoutConfig.payment.disallowedshipping[this.item.method];
            },  
            getCompany: function () {                
                if (typeof quote.billingAddress._latestValue.company !== 'undefined' && quote.billingAddress._latestValue.company !== null) {
                    return quote.billingAddress._latestValue.company;
                }
                return '';                
            },    
            getforCompany   : function () {
                return window.checkoutConfig.payment.showforcompany[this.item.method];
            },  
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getPaymentIcon: function () {
                return window.checkoutConfig.payment.icon[this.item.method];
            },
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
                return this.getKVKDOB() > 0;
            },
            getKVKDOB: function () {
                return (this.getDOB() > 0 && this.getKVK() > 0);
            },  
            showBanks: function(){
                return window.checkoutConfig.payment.banks[this.item.method].length > 0;
            },     
            getBanks: function(){
                return window.checkoutConfig.payment.banks[this.item.method];
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('/paynl/checkout/redirect?nocache='+ (new Date().getTime())));
            },
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
                        "billink_agree": this.billink_agree,
                        "bank_id": this.selectedBank
                    }
                };
            },
            placeOrder: function (data, event) {
                var placeOrder;
                var cocRequired = this.getKVK() == 2;
                var dobRequired = this.getDOB() == 2;
                if (cocRequired) {
                    if (this.billink_agree != true) {
                        alert({
                            title: $.mage.__('Betalingsvoorwaarden'),
                            content: $.mage.__('U dient eerst akkoord te gaan met de betalingsvoorwaarden.'),
                            actions: {
                                always: function(){}
                            }
                        });
                        return false;
                    }
                    if (this.kvknummer == null || this.kvknummer.length < 8) {
                        alert({
                            title: $.mage.__('Ongeldig KVK nummer'),
                            content: $.mage.__('Voer een geldig KVK nummer in.'),
                            actions: {
                                always: function(){}
                            }
                        });
                        return false;
                    }
                }
                if (dobRequired) {
                    if (this.dateofbirth == null || this.dateofbirth.length < 1) {
                        alert({
                            title: $.mage.__('Ongeldig geboortedatum'),
                            content: $.mage.__('Voer een geldig geboortedatum in.'),
                            actions: {
                                always: function(){}
                            }
                        });
                        return false;
                    }
                }
                if (event) {
                    event.preventDefault();
                }
                $('#payment-button').html('Processing').attr('disabled','disabled');
                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);
                $.when(placeOrder).fail(function () {
                    this.isPlaceOrderActionAllowed(true);
                }.bind(this)).done(this.afterPlaceOrder.bind(this));
                return true;
            },
        });
    }
);




