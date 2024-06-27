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
        'mage/translate',
        'Magento_Customer/js/model/customer',
        'ko'
    ],
    function ($, Component, url, placeOrderAction, quote, alert, additionalValidators, translate, customer, ko) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Paynl_Payment/payment/default'
            },
            paymentOption: null,
            paymentOptionsList: [],
            cocnumber: null,
            vatnumber: null,
            dateofbirth: null,
            billink_agree: null,
            companyfield: null,
            pinmoment: null,
            initialize: function () {
                this._super();

                if (this.paymentOption == null) {
                    this.paymentOption = this.getDefaultPaymentOption();
                }

                var defaultPaymentMethod = window.checkoutConfig.payment.defaultpaymentmethod;
                if (!quote.paymentMethod() &&
                    typeof defaultPaymentMethod !== 'undefined' &&
                    typeof defaultPaymentMethod[this.item.method] !== 'undefined' &&
                    defaultPaymentMethod[this.item.method]) {
                    this.selectPaymentMethod();
                }

                return this;
            },
            isVisible:function () {
                var group = window.checkoutConfig.payment.showforgroup[this.item.method];
                if (group) {
                    if (group == 0 && customer.isLoggedIn) {
                        return false;
                    }
                    if (group > 0 && !customer.isLoggedIn) {
                        return false;
                    }
                    if (group != customer.customerData.group_id) {
                        return false;
                    }
                }
                var disallowedShippingMethods = this.getDisallowedShipping();
                if (disallowedShippingMethods) {
                    var currentShippingMethod = this.getCurrentShippingMethod();
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
                if (!this.showPin()){
                    return false;
                }
                return true;
            },
            getCurrentShippingMethod: function () {
                var carrier_code = typeof quote.shippingMethod().carrier_code !== 'undefined' ? quote.shippingMethod().carrier_code + '_' : '';
                var method_code = typeof quote.shippingMethod().method_code !== 'undefined' ? quote.shippingMethod().method_code : '';
                var currentShippingMethod = carrier_code + method_code;
                return currentShippingMethod;
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
            getCompany: function () {
                if (quote.billingAddress.hasOwnProperty('_latestValue') && typeof quote.billingAddress._latestValue !== 'undefined' && quote.billingAddress._latestValue !== null) {
                    if (quote.billingAddress._latestValue.hasOwnProperty('company') && typeof quote.billingAddress._latestValue.company !== 'undefined' && quote.billingAddress._latestValue.company !== null) {
                        return quote.billingAddress._latestValue.company;
                    }
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
            showCompanyField: function () {
                return this.getCompanyField() > 0;
            },
            getCompanyField: function () {
                if (this.getCompany().length > 0) {
                    return false;
                }
                return (typeof window.checkoutConfig.payment.showcompanyfield !== 'undefined') ? window.checkoutConfig.payment.showcompanyfield[this.item.method] : '';
            },
            showKVKAgree: function () {
                if (this.item.method == 'paynl_payment_billink' && this.getKVK() > 0) {
                    return true;
                }
                return false;
            },
            showKVK: function () {
                return (this.getKVK() == 1 || this.getKVK() == 2);
            },
            getKVK: function () {
                return (typeof window.checkoutConfig.payment.showkvk !== 'undefined') ? window.checkoutConfig.payment.showkvk[this.item.method] : '';
            },
            showVAT: function () {
                return (this.getVAT() == 1 || this.getVAT() == 2);
            },
            getVAT: function () {
                if (this.getCompany().length == 0 && (!this.getCompanyField() || this.getCompanyField() == 0)) {
                    return false;
                }
                return (typeof window.checkoutConfig.payment.showvat !== 'undefined') ? window.checkoutConfig.payment.showvat[this.item.method] : '';
            },
            useAdditionalValidation: function () {
                return (typeof window.checkoutConfig.payment.useAdditionalValidation !== 'undefined') ? window.checkoutConfig.payment.useAdditionalValidation : false;
            },
            showDOB: function () {
                return (this.getDOB() == 1 || this.getDOB() == 2);
            },
            getDOB: function () {
                return (typeof window.checkoutConfig.payment.showdob !== 'undefined') ? window.checkoutConfig.payment.showdob[this.item.method] : '';
            },
            showPinMoment: function () {
                return this.getPinMoment() > 0;
            },
            getPinMoment: function () {
                var currentShippingMethod = this.getCurrentShippingMethod();
                return (typeof window.checkoutConfig.payment.showpinmoment !== 'undefined' && window.checkoutConfig.payment.showpinmoment[this.item.method] === '2' && currentShippingMethod === 'instore_pickup') ? window.checkoutConfig.payment.showpinmoment[this.item.method] : '';
            },
            showPin: function () {
                var currentShippingMethod = this.getCurrentShippingMethod();
                var showPinMomentConfig = (typeof window.checkoutConfig.payment.showpinmoment !== 'undefined') ? window.checkoutConfig.payment.showpinmoment[this.item.method] : '';

                if (showPinMomentConfig === '1' && currentShippingMethod !== 'instore_pickup') {
                    return false
                }

                return true
            },
            showKVKDOB: function () {
                return this.getKVKDOB() > 0;
            },
            getKVKDOB: function () {
                return ((this.getDOB() == 1 || this.getDOB() == 2) && (this.getKVK() == 1 || this.getKVK() == 2));
            },
            showPinOption: ko.observable(true),
            showPaymentOptions: function () {
                var pinmoment = window.checkoutConfig.payment.showpinmoment[this.item.method];
                var currentShippingMethod = this.getCurrentShippingMethod();

                if (
                    currentShippingMethod === 'instore_pickup'
                    && pinmoment === '1'
                    && typeof window.checkoutConfig.payment.pinmomentterminal !== 'undefined'
                    && window.checkoutConfig.payment.pinmomentterminal[this.item.method] !== '0'
                ) {
                    this.paymentOption = this.getDefaultPaymomentTerminal();
                    return false;
                }

                return window.checkoutConfig.payment.paymentoptions[this.item.method].length > 0 && window.checkoutConfig.payment.showpaymentoptions[this.item.method] == 1;
            },
            pinmomentChange: function (obj, event) {
                if (
                    event.target.value === '1'
                    && window.checkoutConfig.payment.showpinmoment[this.item.method] === '2'
                    && typeof window.checkoutConfig.payment.pinmomentterminal !== 'undefined'
                    && window.checkoutConfig.payment.pinmomentterminal[this.item.method] !== '0'
                ) {
                    this.paymentOption = this.getDefaultPaymomentTerminal();
                    this.showPinOption(false);
                } else {
                    this.showPinOption(true);
                }
            },
            showPaymentOptionsList: function () {
                return window.checkoutConfig.payment.paymentoptions[this.item.method].length >= 1 && window.checkoutConfig.payment.showpaymentoptions[this.item.method] == 2;
            },
            getPaymentOptions: function () {
                var paymentOptions = window.checkoutConfig.payment.paymentoptions[this.item.method];
                var message = null;
                if (this.item.method == 'paynl_payment_ideal') {
                    message = $.mage.__('Choose your bank');
                }
                if (this.item.method == 'paynl_payment_instore') {
                    message = $.mage.__('Select card terminal');
                }
                if (message && this.showPaymentOptions() === true) {
                    var paymentOption = [];
                    paymentOption['id'] = '';
                    paymentOption['name'] = message;
                    paymentOption['visibleName'] = message;
                    paymentOptions.unshift(paymentOption);
                }
                return paymentOptions;
            },
            getPaymentOptionsList: function () {
                if (!this.showPaymentOptionsList) {
                    return false;
                }
                if (!(this.item.method in this.paymentOptionsList)) {
                    var list = window.checkoutConfig.payment.paymentoptions[this.item.method];
                    var name = 'paymentOptionsList_' + this.item.method;
                    $.each(list, function (i, l) {
                        list[i].radioName = name;
                        list[i].uniqueId = name + '_' + list[i].id;
                        list[i].showLogo = true;
                        if (!('logo' in list[i])) {
                            list[i].showLogo = false;
                            list[i].logo = '';
                        }
                    });
                    this.paymentOptionsList[this.item.method] = list;
                    return list;
                }
                return this.paymentOptionsList[this.item.method];
            },
            getDefaultPaymentOption: function () {
                return window.checkoutConfig.payment.defaultpaymentoption[this.item.method];
            },
            getDefaultPaymomentTerminal: function () {
                return window.checkoutConfig.payment.pinmomentterminal[this.item.method];
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('paynl/checkout/redirect?nocache=' + (new Date().getTime())));
            },
            getCustomField: function (fieldname) {    
                var customFields = [];
                if (quote.billingAddress.hasOwnProperty('_latestValue') && typeof quote.billingAddress._latestValue !== 'undefined' && quote.billingAddress._latestValue !== null) {
                    $.each(quote.billingAddress._latestValue.customAttributes, function (i, l) {
                        var field = quote.billingAddress._latestValue.customAttributes[i]
                        customFields[field.attribute_code] = field.value
                    })
                }
                return ((customFields.hasOwnProperty(fieldname)) ? customFields[fieldname] : null)
            },
            getData: function () {
                var dateofbirth_val = (this.dateofbirth != null && this.dateofbirth.length > 0) ? this.dateofbirth : this.getCustomField('paynl_dob');
                var cocnumber_val = (this.cocnumber != null && this.cocnumber.length > 0) ? this.cocnumber : this.getCustomField('paynl_coc_number');
                var vatnumber_val = (this.vatnumber != null && this.vatnumber.length > 0) ? this.vatnumber : this.getCustomField('paynl_vat_number');

                var dob_format = '';  
        
                if (dateofbirth_val != null) {
                    var dob = new Date(dateofbirth_val)
                    var dd = dob.getDate(), mm = dob.getMonth() + 1, yyyy = dob.getFullYear()
                    dd = (dd < 10) ? '0' + dd : dd
                    mm = (mm < 10) ? '0' + mm : mm
                    dob_format = dd + '-' + mm + '-' + yyyy
                }           

                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        "cocnumber": cocnumber_val,
                        "vatnumber": vatnumber_val,
                        "companyfield": this.companyfield,
                        "dob": dob_format,
                        "billink_agree": this.billink_agree,
                        "payment_option": this.paymentOption,
                        "pinmoment": this.pinmoment
                    }
                };
            },
            placeOrder: function (data, event) {

                if (this.useAdditionalValidation()) {
                    this.validate();
                    additionalValidators.validate();
                }

                var placeOrder;
                var cocRequired = this.getKVK() >= 2;
                var vatRequired = this.getVAT() >= 2;
                var dobRequired = this.getDOB() >= 2;
                var companyfieldRequired = this.getCompanyField() == 2;

                if (companyfieldRequired) {
                    if (this.companyfield == null || this.companyfield.length == 0) {
                        alert({
                            title: $.mage.__('Invalid company'),
                            content: $.mage.__('Enter a valid company name'),
                            actions: {
                                always: function (){}
                            }
                        });
                        return false;
                    }
                }
                if (cocRequired) {
                    if (this.billink_agree != true && this.item.method == 'paynl_payment_billink') {
                        alert({
                            title: $.mage.__('Payment terms'),
                            content: $.mage.__('You must first agree to the payment terms.'),
                            actions: {
                                always: function (){}
                            }
                        });
                        return false;
                    }
                    var cocMethodFieldCheck = (this.cocnumber == null || this.cocnumber.length < 8);
                    var cocCustomFieldCheck = (this.getCustomField('paynl_coc_number') == null || this.getCustomField('paynl_coc_number').length < 8);
                    if (cocMethodFieldCheck && cocCustomFieldCheck) {
                        alert({
                            title: $.mage.__('Invalid COC number'),
                            content: $.mage.__('Enter a valid COC number'),
                            actions: {
                                always: function (){}
                            }
                        });
                        return false;
                    }
                }
                if (vatRequired) {
                    var vatMethodFieldCheck = (this.vatnumber == null || this.vatnumber.length < 8);
                    var vatCustomFieldCheck = (this.getCustomField('paynl_vat_number') == null || this.getCustomField('paynl_vat_number').length < 8);
                    if (vatMethodFieldCheck && vatCustomFieldCheck) {
                        alert({
                            title: $.mage.__('Invalid VAT number'),
                            content: $.mage.__('Enter a valid VAT number'),
                            actions: {
                                always: function (){}
                            }
                        });
                        return false;
                    }
                }
                if (dobRequired) {
                    var dobMethodFieldCheck = (this.dateofbirth == null || this.dateofbirth.length < 1);
                    var dobCustomFieldCheck = (this.getCustomField('paynl_dob') == null || this.getCustomField('paynl_dob').length < 1);
                    if (dobMethodFieldCheck && dobCustomFieldCheck) {
                        alert({
                            title: $.mage.__('Invalid date of birth'),
                            content: $.mage.__('Enter a valid date of birth'),
                            actions: {
                                always: function (){}
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




