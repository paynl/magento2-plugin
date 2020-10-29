/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'underscore',
        'ko',
        'Paynl_Payment/js/modal/payment-modal',
        'Paynl_Payment/js/modal/payment-complete-modal',
        'Paynl_Payment/js/modal/error-modal',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'Paynl_Payment/js/view/payment/method-renderer/pay-cryptography.amd.min'
    ],
    function ($, _, ko, paymentModal, paymentCompleteModal, errorModal, fullScreenLoader, Component, url, placeOrderAction, quote, payCryptography) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            paymentModalContent: ko.observable(null),
            paymentCompleteModalContent: ko.observable(null),
            paymentErrorModalContent: ko.observable(null),
            paymentErrorMessage: ko.observable(null),
            contentHeight: ko.observable(null),
            modalWindow: null,
            isCreditCardFormIsReadyForSubmission: ko.observable(false),
            initialize: function() {
                this._super();

                let baseUrl = require.toUrl('Paynl_Payment');
                let el = document.querySelector('#co-payment-form');
                el.setAttribute('data-pay-encrypt-form', '');

                this.encryptedForm = new payCryptography.EncryptedForm({
                    'debug': true,
                    'public_keys': this.getPublicEncryptionKeys(),
                    'post_url': url.build('paynl/checkout/processEncryptedTransaction'),
                    'payment_complete_url': url.build('paynl/checkout/finish'),
                    'refresh_url': url.build('paynl/checkout/publicKeys'),
                    'bind': {
                        'submit': false
                    },
                    'icons': {
                        'creditcard': {
                            'default': baseUrl + '/images/creditcard/mono/generic.svg',
                            'alipay': baseUrl + '/images/creditcard/logo/alipay.svg',
                            'american-express': baseUrl + '/images/creditcard/logo/amex.svg',
                            'diners-club': baseUrl + '/images/creditcard/logo/diners.svg',
                            'discover': baseUrl + '/images/creditcard/logo/discover.svg',
                            'elo': baseUrl + '/images/creditcard/logo/elo.svg',
                            'hiper': baseUrl + '/images/creditcard/logo/hiper.svg',
                            'hipercard': baseUrl + '/images/creditcard/logo/hipercard.svg',
                            'jcb': baseUrl + '/images/creditcard/logo/jcb.svg',
                            'maestro': baseUrl + '/images/creditcard/logo/maestro.svg',
                            'mastercard': baseUrl + '/images/creditcard/logo/mastercard.svg',
                            'mir': baseUrl + '/images/creditcard/logo/mir.svg',
                            'unionpay': baseUrl + '/images/creditcard/logo/unionpay.svg',
                            'visa': baseUrl + '/images/creditcard/logo/visa.svg'
                        },
                        'cvc': baseUrl + '/images/creditcard/mono/code.svg',
                    }
                });

                let eventDispatcher = this.encryptedForm.getEventDispatcher();
                let self = this;

                let paymentCompleteModalEnabled = this.getPaymentCompleteModalEnabled();
                let paymentFailureModalEnabled = this.getPaymentFailureModalEnabled();
                let paymentCompleteRedirectionTimeout = this.getPaymentCompleteModalRedirectionTimeout();

                eventDispatcher.addListener(
                    payCryptography.Events.onSubmitDataEvent,
                    function(event){
                        event.subject.set('form_key', el.querySelector('input[name="form_key"]').value);
                    }
                );

                eventDispatcher.addListener(
                    payCryptography.Events.onStateChangeEvent,
                    function(event){
                        // skip this function if the current event does not change the loading state.
                        if (event.hasParameter('state') && 'loading' in event.getParameter('state')) {
                            event.getCurrentState().isLoading() ?
                                fullScreenLoader.startLoader() :
                                fullScreenLoader.stopLoader();
                        }

                        self.isCreditCardFormIsReadyForSubmission(event.getCurrentState().isFormReadyForSubmission());
                    },
                    100
                );

                eventDispatcher.addListener(
                    payCryptography.Events.onModalOpenEvent,
                    function(event){
                        event.stopPropagation();
                        self.paymentModalContent('');

                        if (event.subject instanceof payCryptography.PaymentCompleteModal && !paymentCompleteModalEnabled) {
                            return;
                        }

                        if (event.subject instanceof payCryptography.ErrorModal && !paymentFailureModalEnabled) {
                            $(paymentModal.modalWindow).modal('closeModal');
                            self.paymentErrorMessage(`<div class="message error"><div>${event.getSubject().render()}</div></div>`);
                            return;
                        }

                        if (event.subject instanceof payCryptography.PaymentCompleteModal) {
                            self.paymentCompleteModalContent(event.getSubject().render());
                            paymentCompleteModal.showModal();
                            return;
                        }

                        if (event.subject instanceof payCryptography.ErrorModal) {
                            self.paymentErrorModalContent(event.getSubject().render());
                            errorModal.showModal();
                            return;
                        }

                        self.paymentModalContent(event.getSubject().render());
                        paymentModal.showModal();
                    },
                    10
                );

                eventDispatcher.addListener(
                    payCryptography.Events.onModalCloseEvent,
                    function(event){
                        event.stopPropagation();
                        paymentModal.closeModal();
                    },
                    10
                );

                eventDispatcher.addListener(
                    payCryptography.Events.onPaymentCompleteEvent,
                    function(event){
                        event.setParameter('redirection_timeout', paymentCompleteModalEnabled ?
                            paymentCompleteRedirectionTimeout > 0 ? paymentCompleteRedirectionTimeout : 2500  :
                            0
                        )
                    },
                    10
                );

                // custom event that launches when the modal closes, allows for better control
                window.addEventListener('pay-trigger-modal-close', function(e){
                    let event = new payCryptography.ModalCloseEvent(e);
                    eventDispatcher.dispatch(event, payCryptography.Events.onModalCloseEvent);

                    eventDispatcher.dispatch(new payCryptography.StateChangeEvent(e, {
                        'state': {modalOpen: false, formSubmitted: false}
                    }), payCryptography.Events.onStateChangeEvent);
                });

                return this;
            },
            initPaymentModal: function (element) {
                paymentModal.createModal(element);
            },
            initErrorModal: function (element) {
                errorModal.createModal(element);
            },
            initPaymentCompleteModal: function (element) {
                paymentCompleteModal.createModal(element);
            },
            getPublicEncryptionKeys: function(){
                if ('public_encryption_keys' in window.checkoutConfig.payment) {
                    return JSON.parse(window.checkoutConfig.payment.public_encryption_keys[this.item.method]);
                }

                return [];
            },
            defaults: {
                template: 'Paynl_Payment/payment/visamastercard'
            },
            initializePaymentForm: function(){
                this.encryptedForm.init();
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
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getPaymentIcon: function () {
                return window.checkoutConfig.payment.icon[this.item.method];
            },
            getPaymentCompleteModalEnabled: function () {
                return window.checkoutConfig.payment.cse_modal_payment_complete[this.item.method];
            },
            getPaymentFailureModalEnabled: function () {
                return window.checkoutConfig.payment.cse_modal_payment_failure[this.item.method];
            },
            getPaymentCompleteModalRedirectionTimeout: function () {
                return window.checkoutConfig.payment.cse_modal_payment_complete_redirection_timeout[this.item.method];
            },
            getCcMonths: function () {
                return window.checkoutConfig.payment.cc_months[this.getCode()];
            },
            getCcYears: function () {
                return window.checkoutConfig.payment.cc_years[this.getCode()];
            },
            getCcMonthsValues: function () {
                return _.map(this.getCcMonths(), function (value, key) {
                    return {
                        'value': key,
                        'month': value
                    };
                });
            },
            getCcYearsValues: function () {
                return _.map(this.getCcYears(), function (value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
                });
            },
            getCurrentMonth: function(el){
                let date = new Date();
                return date.getMonth() + 1;
            },
            placeOrder: function(e){
                let self = this;
                self.encryptedForm.state.getElementFromReference(payCryptography.Elements.form);
                self.isPlaceOrderActionAllowed(false);
                self.getPlaceOrderDeferredObject()
                    .fail(
                        function (e) {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    )
                    .done(
                    function (orderId) {
                        self.afterPlaceOrder();
                        self.encryptedForm.handleFormSubmission(
                            self.encryptedForm.state.getElementFromReference(payCryptography.Elements.form)
                        );
                    }
                ).always(function(){
                    self.isPlaceOrderActionAllowed(true);
                });

                return false;
            }
        });
    }
);
