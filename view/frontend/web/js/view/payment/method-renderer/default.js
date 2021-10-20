/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate'
    ],
    function ($, Component, url, placeOrderAction, quote, alert, additionalValidators, translate) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Paynl_Payment/payment/default'
            },
            subOption: null,
            kvknummer: null,
            vatnumber: null,
            dateofbirth: null,
            billink_agree: null,
            initialize: function () {
                this._super();

                if(this.subOption == null){
                    this.subOption = this.getDefaultSubOption();
                }

                var defaultPaymentMethod = window.checkoutConfig.payment.defaultpaymentoption;
                if (!quote.paymentMethod() &&
                    typeof defaultPaymentMethod !== 'undefined' &&
                    typeof defaultPaymentMethod[this.item.method] !== 'undefined' &&
                    defaultPaymentMethod[this.item.method])  {
                    this.selectPaymentMethod();
                }

                return this;
            },
            isVisible:function() {
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
                if (this.getforCompany() == 1 && this.getCompany().length != 0) {
                    return false;
                }
                if (this.getforCompany() == 2 && this.getCompany().length == 0) {
                    return false;
                }
                if (!this.currentIpIsValid()) {
                    return false;
                }
                if (!this.currentAgentIsValid()) {
                    return false;
                }
                return true;
            },
            currentIpIsValid: function () {
                return window.checkoutConfig.payment.currentipisvalid[this.item.method];
            },
            currentAgentIsValid: function () {
                return window.checkoutConfig.payment.currentagentisvalid[this.item.method];
            },
            getDisallowedShipping: function () {
                return window.checkoutConfig.payment.disallowedshipping[this.item.method];
            },
            getIconSize: function(){
                var size = window.checkoutConfig.payment.iconsize;
                if(size){
                    return 'pay_icon_size_' + size;
                }
                return '';                
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
            showKVKAgree: function(){
                if(this.item.method == 'paynl_payment_billink' && this.getKVK() > 0){
                    return true;
                }
                return false;
            },
            showKVK: function () {
                return this.getKVK() > 0;
            },
            getKVK: function () {
                return (typeof window.checkoutConfig.payment.showkvk !== 'undefined') ? window.checkoutConfig.payment.showkvk[this.item.method] : '';
            },
            showVAT: function () {
                return this.getVAT() > 0;
            },
            getVAT: function () {
                if(this.getCompany().length == 0){
                    return false;
                }
                return (typeof window.checkoutConfig.payment.showvat !== 'undefined') ? window.checkoutConfig.payment.showvat[this.item.method] : '';
            },
            useAdditionalValidation: function () {
                return (typeof window.checkoutConfig.payment.useAdditionalValidation !== 'undefined') ? window.checkoutConfig.payment.useAdditionalValidation : false;
            },
            showDOB: function () {
                return this.getDOB() > 0;
            },
            getDOB: function () {
                return (typeof window.checkoutConfig.payment.showdob !== 'undefined') ? window.checkoutConfig.payment.showdob[this.item.method] : '';
            },
            showKVKDOB: function () {
                return this.getKVKDOB() > 0;
            },
            getKVKDOB: function () {
                return (this.getDOB() > 0 && this.getKVK() > 0);
            },
            showSubOptions: function(){
                return window.checkoutConfig.payment.suboptions[this.item.method].length > 0;
            },
            getSubOptions: function(){
                return window.checkoutConfig.payment.suboptions[this.item.method];
            },
            getDefaultSubOption: function(){               
                return window.checkoutConfig.payment.defaultsuboption[this.item.method];
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('paynl/checkout/redirect?nocache='+ (new Date().getTime())));
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
                        "vatnumber": this.vatnumber,
                        "dob": dob_format,
                        "billink_agree": this.billink_agree,
                        "sub_option_id": this.subOption
                    }
                };
            },
            placeOrder: function (data, event) {

                if(this.useAdditionalValidation()) {
                    this.validate();
                    additionalValidators.validate();
                }

                var placeOrder;
                var cocRequired = this.getKVK() == 2;
                var vatRequired = this.getVAT() == 2;
                var dobRequired = this.getDOB() == 2;
                if (cocRequired) {
                    if (this.billink_agree != true && this.item.method == 'paynl_payment_billink') {
                        alert({
                            title: $.mage.__('Payment terms'),
                            content: $.mage.__('You must first agree to the payment terms.'),
                            actions: {
                                always: function(){}
                            }
                        });
                        return false;
                    }
                    if (this.kvknummer == null || this.kvknummer.length < 8) {
                        alert({
                            title: $.mage.__('Invalid COC number'),
                            content: $.mage.__('Enter a valid COC number'),
                            actions: {
                                always: function(){}
                            }
                        });
                        return false;
                    }
                }
                if (vatRequired) {
                    if (this.vatnumber == null || this.vatnumber.length < 8) {
                        alert({
                            title: $.mage.__('Invalid VAT-id'),
                            content: $.mage.__('Enter a valid VAT-id'),
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
                            title: $.mage.__('Invalid date of birth'),
                            content: $.mage.__('Enter a valid date of birth'),
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

                var objButton = $(event.target);
                if (objButton.length > 0) {
                    if (objButton.is('span')) {
                        objButton = objButton.parent();
                    }
                    var curText = objButton.text();
                    objButton.text($.mage.__('Processing')).prop('disabled', true);
                }

                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);
                $.when(placeOrder).fail(function () {
                    if (objButton.length > 0) {
                        objButton.text(curText).prop('disabled', false);
                    }
                    this.isPlaceOrderActionAllowed(true);
                }.bind(this)).done(this.afterPlaceOrder.bind(this));
                return true;
            },
        });
    }
);




