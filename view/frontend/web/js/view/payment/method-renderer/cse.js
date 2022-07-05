/*browser:true*/
/*global define*/

define(
    [
        'Magento_Checkout/js/model/full-screen-loader',
        'ko',
        'jquery',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate',
        'Magento_Customer/js/model/customer',
        'Paynl_Payment/js/view/payment/method-renderer/pay-cryptography.amd',
        'Paynl_Payment/js/modal/payment-modal',
        'Paynl_Payment/js/modal/payment-complete-modal',
        'Paynl_Payment/js/modal/error-modal',
    ],
    function (fullScreenLoader, ko, $, _, Component, url, placeOrderAction, quote, alert, additionalValidators, $t, customer, payCryptography, paymentModal, paymentCompleteModal, errorModal) {
        'use strict';
        const orderButtonText = 'Place Order';
        const MODAL_POPUP_CUSTOM = 'popup_custom';
        const MODAL_POPUP_NATIVE = 'popup_native';
        const MODAL_POPUP_INLINE = 'inline';
        const MODAL_NONE = 'none';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: { template: 'Paynl_Payment/payment/cse' },

            paymentModalContent: ko.observable(null),
            paymentCompleteModalContent: ko.observable(null),
            paymentErrorModalContent: ko.observable(null),
            contentHeight: ko.observable(null),
            modalWindow: null,
            activeModal: null,
            paymentInlineMessage: ko.observable(null),
            isCreditCardFormIsReadyForSubmission: ko.observable(false),
            isPlaceOrderActionAllowed: ko.observable(true),
            paymentOption: null,
            paymentOptionsList: [],
            kvknummer: null,
            vatnumber: null,
            dateofbirth: null,
            billink_agree: null,
            payDebugEnabled: false,
            testMode: false,

            initialize: function () {
                this._super();

                if(this.paymentOption == null){
                    this.paymentOption = this.getDefaultPaymentOption();
                }

                var defaultPaymentMethod = window.checkoutConfig.payment.defaultpaymentmethod;
                if (!quote.paymentMethod() &&
                    typeof defaultPaymentMethod !== 'undefined' &&
                    typeof defaultPaymentMethod[this.item.method] !== 'undefined' &&
                    defaultPaymentMethod[this.item.method])  {
                    this.selectPaymentMethod();
                }

                let self = this;
                let baseUrl = require.toUrl('Paynl_Payment');
                let el = document.querySelector('#co-payment-form');
                el.setAttribute('data-pay-encrypt-form', '');

                let thekeys = this.getPublicEncryptionKeys();
                let publicEncryptionKeys = thekeys;
                let entityid = 123;
                self.testMode = this.getCseConfig('testMode');

                this.encryptedForm = new payCryptography.EncryptedForm({
                    'debug':                false,
                    'public_keys':          publicEncryptionKeys,
                    'language':             this.getLanguage(),
                    'post_url':             url.build('rest/V1/paynl/cse'),
                    'status_url':           url.build('rest/V1/paynl/cse/status?transaction_id=%transaction_id%'),
                    'authorization_url':    url.build('rest/V1/paynl/cse/authorization'),
                    'authentication_url':   url.build('rest/V1/paynl/cse/authentication'),
                    'payment_complete_url': '',
                    'refresh_url':          url.build('paynl/checkout/publicKeys'),
                    'form_input_payload_name': 'pay_encrypted_data',
                    'form_selector': 'data-pay-encrypt-form', // attribute to look for to identify the target form
                    'field_selector': 'data-pay-encrypt-field', // attribute to look for to identify the target form elements
                    'field_value_reader': 'name', // grabs the required data keys from this attribute*/
                    'bind': {
                        'submit': false
                    },
                    'icons': {
                        'creditcard': {
                            'default': baseUrl + '/images/creditcard/cc-front.svg',
                            'alipay': baseUrl + '/images/creditcard/cc-alipay.svg',
                            'american-express': baseUrl + '/images/creditcard/cc-amex.svg',
                            'diners-club': baseUrl + '/images/creditcard/cc-diners-club.svg',
                            'discover': baseUrl + '/images/creditcard/cc-discover.svg',
                            'elo': baseUrl + '/images/creditcard/cc-elo.svg',
                            'hiper': baseUrl + '/images/creditcard/cc-hiper.svg',
                            'hipercard': baseUrl + '/images/creditcard/cc-hipercard.svg',
                            'jcb': baseUrl + '/images/creditcard/cc-jcb.svg',
                            'maestro': baseUrl + '/images/creditcard/cc-maestro.svg',
                            'mastercard': baseUrl + '/images/creditcard/cc-mastercard.svg',
                            'mir': baseUrl + '/images/creditcard/cc-mir.svg',
                            'unionpay': baseUrl + '/images/creditcard/cc-unionpay.svg',
                            'visa': baseUrl + '/images/creditcard/cc-visa.svg'
                        },
                        'cvc': baseUrl + '/images/creditcard/cc-back.svg',
                    }
                });
                let eventDispatcher = this.encryptedForm.getEventDispatcher();

                /* Retrieve settings */
                let successPopup = this.getCseConfig('cse_success_popup');
                let paymentPopup = this.getCseConfig('cse_payment_popup');
                let errorPopup = this.getCseConfig('cse_error_popup');
                this.payDebugEnabled = this.getCseConfig('cse_debug') == '1';
                let redirectTimeout = this.getCseConfig('cse_finish_delay') * 1000;

                /* cse_pay_debug */
                self.payDebug('successPopup: ' + successPopup);
                self.payDebug('paymentPopup: ' + paymentPopup);
                self.payDebug('errorPopup: ' + errorPopup);
                self.payDebug('redirectTimeout: ' + redirectTimeout);

                eventDispatcher.addListener(payCryptography.Events.onPaymentCanceledEvent, function (event) {
                        self.payDebug('Cancel Event - reset button');
                        self.setPlaceOrderButton($t(orderButtonText), false);
                        self.isPlaceOrderActionAllowed(true);
                    },
                    10
                );

                eventDispatcher.addListener(payCryptography.Events.onStateChangeEvent, function (event)  {
                    /* Skip this function if the current event does not change the loading state. */
                    if (event.hasParameter('state') && 'loading' in event.getParameter('state')) {
                        event.getCurrentState().isLoading() ? fullScreenLoader.startLoader() : fullScreenLoader.stopLoader();
                    }
                    if (event.getCurrentState().isFormReadyForSubmission()) {
                        if (self.testMode) {
                            let cardHolder = $('#card-holder').val();
                            self.encryptedForm.setPaymentPostUrl(url.build('rest/V1/paynl/cse?mode=' + cardHolder));
                        }

                        self.isCreditCardFormIsReadyForSubmission(event.getCurrentState().isFormReadyForSubmission());
                    }
                }, 100);

                eventDispatcher.addListener(payCryptography.Events.onActionableResponseEvent, function (event) {
                    self.payDebug('event.onActionableResponseEvent');
                    let orderEntityId = event.subject.data.entityId;
                    let transid = event.subject.data.orderId;
                    let newurl = url.build('paynl/checkout/finish/?entityid=' + orderEntityId + '&orderid=' + transid);
                    if (orderEntityId != 'undefined' && orderEntityId != undefined) {
                        self.payDebug('New complete url: ' + newurl);
                        self.encryptedForm.setPaymentCompleteUrl(newurl);
                    }
                });

                eventDispatcher.addListener(payCryptography.Events.onModalCloseEvent, function (event) {
                    event.stopPropagation();

                    self.payDebug('onModalCloseEvent. Hiding pay-cse-custom-modal, hiding activeModal');
                    $('#pay-cse-custom-modal').hide();

                    if (self.activeModal !== null) {
                        self.payDebug('Closing modal');
                        self.payDebug(self.activeModal);
                        self.activeModal.closeModal();
                        self.activeModal = null;
                    }
                }, 10);

                eventDispatcher.addListener(payCryptography.Events.onPaymentFailedEvent, function (event) {
                    self.payDebug('onPaymentFailedEvent');
                    self.setPlaceOrderButton($t(orderButtonText), false);
                    self.isPlaceOrderActionAllowed(true);
                }, 10);

                eventDispatcher.addListener(payCryptography.Events.onModalOpenEvent, function(event) {
                    let eventSubject = event.getSubject()
                    self.payDebug('onModalOpenEvent-Custom');
                    event.stopPropagation();
                    self.paymentModalContent('');

                    if (self.activeModal !== null) {
                        self.payDebug('Closing modal');
                        self.payDebug(self.activeModal);
                        self.activeModal.closeModal();
                    }

                    if (event.subject instanceof payCryptography.PaymentCompleteModal) {
                        self.payDebug('instanceof payCryptography.PaymentCompleteModal');
                        self.payDebug('successPopup: ' + successPopup);
                        fullScreenLoader.stopLoader();
                        self.setPlaceOrderButton($t(orderButtonText), false);
                        self.isPlaceOrderActionAllowed(true);
                        self.hidePlaceOrderButton();

                        switch (successPopup) {
                            case MODAL_POPUP_INLINE:
                                let succesHtml = '<b>' + $t('Payment successfull') + '</b><br>' +  $t('You will be redirected automatically.');
                                self.paymentInlineMessage('<div class="message success"><div>' + succesHtml + '</div></div>');
                                break;

                            case MODAL_POPUP_CUSTOM:
                                let sj1 = event.getSubject();
                                let tessies = sj1.render();
                                $('#pay-cse-custom-modal').text($t('Payment successfull')).show();
                                break;

                            case MODAL_POPUP_NATIVE:
                                self.activeModal = paymentCompleteModal;
                                self.paymentCompleteModalContent('<p>' + $t('Payment successfull') + '. ' + $t('You will be redirected automatically.') + '</p>');
                                paymentCompleteModal.showModal();
                                break;

                            default:
                                break;
                        }
                        return;
                    }

                    if (event.subject instanceof payCryptography.ErrorModal) {
                        self.payDebug('payCryptography.ErrorModal');
                        if (errorPopup == MODAL_POPUP_NATIVE) {
                            self.activeModal = errorModal;
                            self.paymentErrorModalContent(event.getSubject().render());
                            errorModal.showModal();
                        } else {
                            self.paymentInlineMessage(`<div class="message error"><div>${event.getSubject().render()}</div></div>`);
                        }
                        return;
                    }

                    /* We're still here.. we're assuming paymentmodal then */
                    self.activeModal = paymentModal;

                    if (eventSubject != null) {
                        if (paymentPopup == MODAL_POPUP_NATIVE) {
                            $('#pay-cse-custom-modal').html(eventSubject.render()).show();
                        } else {
                            self.paymentModalContent(eventSubject.render());
                            paymentModal.showModal();
                        }
                    }

                    self.payDebug('showing modal');

                    fullScreenLoader.stopLoader();
                }, 10);

                eventDispatcher.addListener(payCryptography.Events.onPaymentCompleteEvent, function (event) {
                    self.payDebug('onPaymentCompleteEvent custom');
                    let pol = self.encryptedForm.getPoller();
                    pol.clear();
                    let iTimeout = (successPopup == MODAL_NONE) ? 0 : (redirectTimeout > 0 ? redirectTimeout : 2500);
                    self.payDebug('Timout to: ' + iTimeout);
                    event.setParameter('redirection_timeout', iTimeout);
                }, 10);

                /* Custom event that launches when the modal closes, allows for better control */
                window.addEventListener('pay-trigger-modal-close', function (event) {
                    self.payDebug('pay-trigger-modal-close');
                    eventDispatcher.dispatch(new payCryptography.StateChangeEvent(event, {
                        'state': {modalOpen: false, formSubmitted: false}
                    }), payCryptography.Events.onStateChangeEvent);
                    self.activeModal = null;
                    /* Making sure any content/polling from this content will stop working */
                    self.paymentModalContent('');
                    let isPolling = self.encryptedForm.state.isPolling();
                    if (isPolling) {
                        let pol = self.encryptedForm.getPoller();
                        pol.clear();
                        self.setPlaceOrderButton(orderButtonText, false);
                        self.isPlaceOrderActionAllowed(true);
                    }
                });

                return this;
            },
            getCseConfig: function (item) {
                return window.checkoutConfig.payment[item];
            },
            payDebug: function (text) {
                if (this.payDebugEnabled) {
                    if (typeof text == 'string') {
                        console.log('PAY. - ' + text);
                    } else {
                        console.log(text);
                    }
                }
            },
            setPlaceOrderButton: function (tekst, setLoadingImage) {
                let objButton = $('#cse_place_order');
                if (objButton) {
                    if (setLoadingImage === true) {
                        $('#cse_place_order').addClass('loaderImage');
                    } else {
                        objButton.removeClass('loaderImage');
                    }
                    objButton.html(tekst);
                } else {
                    self.payDebug('cant find button');
                }
            },
            hidePlaceOrderButton: function () {
                let objButton = $('#cse_place_order');
                if (objButton) {
                    objButton.hide();
                }
            },
            initPaymentCompleteModal: function (element) {
                paymentCompleteModal.createModal(element);
            },
            initPaymentModal: function (element) {
                paymentModal.createModal(element);
            },
            initErrorModal: function (element) {
                errorModal.createModal(element);
            },
            initializePaymentForm: function () {
                this.encryptedForm.init();
                let cseColor = this.getCseConfig('cse_color');
                if (cseColor != 'none') {
                    $('#pay-cse-method').addClass('fieldStyle_' + cseColor);
                }
                if (this.testMode) {
                    $('#pay-cse-title').append(' (Test Mode)');
                }
                let self = this;
                $('#pay-cse-fieldset input, #pay-cse-fieldset select').on('keydown', function (e) {
                    var code = e.keyCode || e.which;
                    if (code == 13) {
                        self.payDebug('Preventing submit');
                        e.stopPropagation();
                        e.preventDefault();
                        return false;
                    }
                });
            },
            getLanguage: function () {
                return window.checkoutConfig.payment.language[this.item.method];
            },
            getPublicEncryptionKeys: function () {
                if ('public_encryption_keys' in window.checkoutConfig.payment) {
                    return JSON.parse(window.checkoutConfig.payment.public_encryption_keys[this.item.method]);
                }
                return [];
            },
            prependChooseOption: function (items, key, label) {
                let options = [{
                    'value': '',
                    [key]: label
                }];

                _.map(items, function (value, itemKey) {
                    options.push({
                        'value': itemKey,
                        [key]: value.toString().toUpperCase()
                    });
                });

                return options;
            },
            setEmptyValuesDisabled: function (option, item) {
                ko.applyBindingsToNode(option, {disable: item.value === ''}, item);
            },
            getCcMonthsValues: function () {
                return this.prependChooseOption(window.checkoutConfig.payment.cc_months[this.getCode()], 'month', $t('Select'));
            },
            getCcYearsValues: function () {
                let yrs = window.checkoutConfig.payment.cc_years[this.getCode()];
                return this.prependChooseOption(yrs, 'year', $t('Select'));
            },
            isVisible: function () {
                return true;
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
            getIconSize: function () {
                var size = window.checkoutConfig.payment.iconsize;
                if (size) {
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
            getforCompany: function () {
                return window.checkoutConfig.payment.showforcompany[this.item.method];
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getPaymentIcon: function () {
                return window.checkoutConfig.payment.icon[this.item.method];
            },
            showKVKAgree: function () {
                if (this.item.method == 'paynl_payment_billink' && this.getKVK() > 0) {
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
                if (this.getCompany().length == 0) {
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
            showPaymentOptions: function () {
                if (window.checkoutConfig.payment.hidepaymentoptions[this.item.method] == 1) {
                    return false;
                }
                return window.checkoutConfig.payment.paymentoptions[this.item.method].length > 0 && window.checkoutConfig.payment.showpaymentoptions[this.item.method] != 2;
            },
            showPaymentOptionsList: function () {
                return window.checkoutConfig.payment.paymentoptions[this.item.method].length >= 1 && window.checkoutConfig.payment.showpaymentoptions[this.item.method] == 2;
            },
            getPaymentOptions: function () {
                return window.checkoutConfig.payment.paymentoptions[this.item.method];
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
            getData: function () {
                var dob_format = '';
                if (this.dateofbirth != null) {
                    var dob = new Date(this.dateofbirth);
                    var dd = dob.getDate(), mm = dob.getMonth() + 1, yyyy = dob.getFullYear();
                    dd = (dd < 10) ? '0' + dd : dd;
                    mm = (mm < 10) ? '0' + mm : mm;
                    dob_format = dd + '-' + mm + '-' + yyyy;
                }
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        "kvknummer": this.kvknummer,
                        "vatnumber": this.vatnumber,
                        "dob": dob_format,
                        "billink_agree": this.billink_agree,
                        "payment_option": this.paymentOption
                    }
                };
            },
            placeOrder: function (e) {
                let self = this;
                self.payDebug('placeOrder CSE');

                if (!self.isCreditCardFormIsReadyForSubmission()) {
                    self.payDebug('Form not ready');
                    return;
                }
                self.paymentInlineMessage('');
                self.setPlaceOrderButton($t('Processing Payment'), true);
                self.encryptedForm.state.getElementFromReference(payCryptography.Elements.form);
                self.isPlaceOrderActionAllowed(false);
                self.getPlaceOrderDeferredObject()
                    .fail(function (e) {
                            self.payDebug(e);
                            let message = 'Something went wrong';
                            if (e.hasOwnProperty('responseJSON')) {
                                let jason = e.responseJSON;
                                if (jason.hasOwnProperty('message')) {
                                    message = jason.message;
                                }
                            }
                            self.paymentInlineMessage('<div class="message error"><div>' + message + '</div></div>');
                            self.setPlaceOrderButton($t(orderButtonText), false);
                            self.isPlaceOrderActionAllowed(true);
                        }
                    )
                    .done(function (orderId) {
                            self.afterPlaceOrder();
                            self.encryptedForm.handleFormSubmission(
                                self.encryptedForm.state.getElementFromReference(payCryptography.Elements.form)
                            );
                        }
                    );
                return false;
            }
        });
    }
);
