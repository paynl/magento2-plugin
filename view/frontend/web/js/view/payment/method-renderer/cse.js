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
        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: { template: 'Paynl_Payment/payment/cse' },

            paymentModalContent: ko.observable(null),
            paymentCompleteModalContent: ko.observable(null),
            paymentErrorModalContent: ko.observable(null),
            contentHeight: ko.observable(null),
            modalWindow: null,
            activeModal: null,

            paymentErrorMessage: ko.observable(null),
            isCreditCardFormIsReadyForSubmission: ko.observable(false),
            paymentOption: null,
            paymentOptionsList: [],
            kvknummer: null,
            vatnumber: null,
            dateofbirth: null,
            billink_agree: null,
            initialize: function () {
                this._super();
                /*
                                if(this.paymentOption == null){
                                    this.paymentOption = this.getDefaultPaymentOption();
                                }

                                console.log('testtest');

                                var defaultPaymentMethod = window.checkoutConfig.payment.defaultpaymentmethod;
                                if (!quote.paymentMethod() &&
                                    typeof defaultPaymentMethod !== 'undefined' &&
                                    typeof defaultPaymentMethod[this.item.method] !== 'undefined' &&
                                    defaultPaymentMethod[this.item.method])  {
                                    this.selectPaymentMethod();
                                }
                */
                let baseUrl = require.toUrl('Paynl_Payment');


                let el = document.querySelector('#co-payment-form');
                el.setAttribute('data-pay-encrypt-form', '');

                let thekeys = this.getPublicEncryptionKeys();
                let publicEncryptionKeys = thekeys;
                let entityid = 123;

                this.encryptedForm = new payCryptography.EncryptedForm({
                    'debug':                false,
                    'public_keys':          publicEncryptionKeys,
                    'language':             this.getLanguage(),
                    'post_url':             url.build('rest/V1/paynl/cse'),
                    'status_url':           url.build('rest/V1/paynl/cse/status?transaction_id=%transaction_id%'),
                    'authorization_url':    url.build('rest/V1/paynl/cse/authorization'),
                    'authentication_url':   url.build('rest/V1/paynl/cse/authentication'),
                    'payment_complete_url': 'https://demo.pay.nl/cse/complete.php', //url.build('paynl/checkout/finish'),
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

                console.log('debug X1');

                let eventDispatcher = this.encryptedForm.getEventDispatcher();
                let self = this;

                let paymentCompleteModalEnabled = 1; //this.getPaymentCompleteModalEnabled();
                let paymentFailureModalEnabled = 0; //this.getPaymentFailureModalEnabled();
                let paymentCompleteRedirectionTimeout = 10000; //this.getPaymentCompleteModalRedirectionTimeout();

                console.log('paymentCompleteModalEnabled: ' + paymentCompleteModalEnabled);
                console.log('paymentFailureModalEnabled: ' + paymentFailureModalEnabled);
                console.log('paymentCompleteRedirectionTimeout: ' + paymentCompleteRedirectionTimeout);

                console.log('cancled - doint nothin in custom');

                //let elr = document.querySelector('#cse_place_order');
//                let elr = document.getElementById('cse_place_order');


                //elr.value = 'fewfewfefwfw';


                eventDispatcher.addListener(payCryptography.Events.onPaymentCanceledEvent, function(event)
                {
                    console.log('cancled - doint nothin in custom');
                    self.setBitton('Place order - CSE', false);
                    return;
                        //let tekst = event.getSubject().render();
/*
                        alert({
                            title: $.mage.__('titel tekst'),
                            content: $.mage.__('canceled'),
                            actions: {
                                always: function(){}
                            }
                        });*/
                    },
                    10
                );


                eventDispatcher.addListener(payCryptography.Events.onStateChangeEvent, function(event) {
                        // skip this function if the current event does not change the loading state.
                        if (event.hasParameter('state') && 'loading' in event.getParameter('state'))
                        {
                            //event.getCurrentState().isLoading() ? self.setBitton('aant goan', true) : self.setBitton('Place order', false);

                //            event.getCurrentState().isLoading() ? fullScreenLoader.startLoader() : fullScreenLoader.stopLoader();
                            event.getCurrentState().isLoading() ? console.log('startLoader') : console.log('stoploader');




                        }

                        // skip this function if the current event does not change the loading state.
                        if (event.hasParameter('state')) {
                            if(event.getCurrentState().isPolling() & event.getCurrentState().isModalClosed())
                            {
/*
                                console.log('stopping polling');
                                //let pollingResponse = event.subject();


                                let poller = self.encryptedForm.getPoller();
                                poller.clear();
*/
                                //E/ventDispatcher.getInstance().dispatch(new PaymentCanceledEvent(pollingResponse), Events.onPaymentCanceledEvent);
                                //
                                //eventDispatcher.dispatch(new payCryptography.PaymentCanceledEvent(pollingResponse), payCryptography.Events.onPaymentCanceledEvent);


                            }
                        }

                    if (event.getCurrentState().isLoading()) {
                        console.log('lading');
                    }
                        if (event.getCurrentState().isFormReadyForSubmission()) {
                            self.isCreditCardFormIsReadyForSubmission(event.getCurrentState().isFormReadyForSubmission());
                        }
                    },
                    100
                );

                /*
                eventDispatcher.addListener(payCryptography.Events.onPaymentRequiresTdsMethodEvent, function(event) {
                    console.log('event.onPaymentRequiresTdsMethodEvent');
                    let ent =  event.subject.data.entityId;
                    let transid = event.subject.data.orderId;
                    let newurl = url.build('paynl/checkout/finish/?entityid=' + ent);
                    console.log('transid: '  + transid);
//                    self.encryptedForm.setPaymentCompleteUrl(newurl);
                });*/

                eventDispatcher.addListener(payCryptography.Events.onActionableResponseEvent, function(event)
                {
                    console.log('event.onActionableResponseEvent');

                    let enty    = event.subject.data.entityId;
                    let transid = event.subject.data.orderId;
                    let newurl = url.build('paynl/checkout/finish/?entityid=' + enty + '&orderid=' + transid);

                    if (enty != 'undefined' && enty != undefined)
                    {
                        console.log('New complete url: ' + newurl);
                        self.encryptedForm.setPaymentCompleteUrl(newurl);
                    }
                });


                eventDispatcher.addListener(payCryptography.Events.onModalCloseEvent, function (event)
                {
                    event.stopPropagation(); // this halts our internals from handling the event

                    console.log('onModalCloseEvent. Hiding wouter, hiding activeModal');
                    $('#wouter').hide();

                    if (self.activeModal !== null) {
                        console.log('Closing modal');
                        console.log(self.activeModal);
                        self.activeModal.closeModal();
                        self.activeModal = null;

                    } else {
                        console.log('NOT closing modal cause none active');
                    }
                }, 10);


                eventDispatcher.addListener(payCryptography.Events.onPaymentFailedEvent, function (event)
                {
                    //  event.stopPropagation(); // this halts our internals from handling the event
                    // custom logic here
                    console.log('onPaymentFailedEvent');

                    self.setBitton($t('Place Order'), false);

                }, 10);


                eventDispatcher.addListener(payCryptography.Events.onSubmitDataEvent, function (event) {
                        console.log('event.onSubmitDataEvent');
                        // self.paymentErrorMessage('');
                        // event.subject.set('form_key', el.querySelector('input[name="form_key"]').value);

                        //self.setBitton('Processing payment', true);
                    }
                );

                eventDispatcher.addListener(payCryptography.Events.onModalOpenEvent, function(event) {

                        console.log(event);
                        console.log(event.subject);
                        console.log('onModalOpenEvent 2');
                        event.stopPropagation();



                        //self.paymentModalContent('zo ff legen');

                        if (self.activeModal !== null)
                        {
                            console.log('Closing modal');
                            console.log(self.activeModal);
                            self.activeModal.closeModal();


                        } else {
                            console.log('NOT closing modal cause none active');
                        }


                        if (event.subject instanceof payCryptography.PaymentCompleteModal)
                        {
                            fullScreenLoader.stopLoader();


                            let useNativeModals = false;

                                if (!paymentCompleteModalEnabled)
                                {
                                    console.log('payment completed, modal disabled');
                                    return;
                                }
                                else
                                {
                                    if(useNativeModals)
                                    {
                                        $('#cse-status').text(sj.render());
                                    } else {
                                        console.log("PaymentCompleteModal ");
                                        self.activeModal = paymentCompleteModal;
                                        self.paymentCompleteModalContent(`<p>${$t('Thanks for your order. We\'ll email you order details and tracking information.')}</p>`);
                                        paymentCompleteModal.showModal();
                                        return;
                                    }
                                }
                        }


                            if (event.subject instanceof payCryptography.ErrorModal && !paymentFailureModalEnabled)
                            {
                                console.log('payCryptography.ErrorModal');
                                self.paymentErrorMessage(`<div class="message error"><div>${event.getSubject().render()}</div></div>`);
                                return;
                            }


                            if (event.subject instanceof payCryptography.ErrorModal)
                            {
                                //errorModal.closeModal();
                                console.log('Event ErrorModal');
                                self.activeModal = errorModal;
                                self.paymentErrorModalContent(event.getSubject().render());
                                errorModal.setTitle('deTitel');
                                errorModal.showModal();
                                return;
                            }



                    //setTimeout(function()
                    //{
                        let sj = event.getSubject()

                        self.activeModal = paymentModal;
                        if (sj != null) {

                            $('#wouter').html(sj.render());

                        } else {
                            self.paymentModalContent('Geen content');
                        }

                        $('#wouter').show();

                        console.log('showing modal');
                        fullScreenLoader.stopLoader();

                        //paymentModal.showModal();
                    //}, 5000);

                    },
                    10
                );


                eventDispatcher.addListener(payCryptography.Events.onPaymentCompleteEvent, function (event)
                {
                    console.log('onPaymentCompleteEvent custom');

                    let pol = self.encryptedForm.getPoller();
                    pol.clear();

                    //self.setBitton($t('Payment completed succesfully'));
                    self.hideBitton();

                    //                    self.encryptedForm.setPaymentCompleteUrl(newurl);

                    console.log(event)
                    console.log(event.getParameter('paymentCompleteUrl'));


                    $('#cse-status').html('<a href="'+event.getParameter('paymentCompleteUrl')+'"><b>Betaling gelukt.</b><br>U wordt automatisch doorgeschakeld (Of klik hier).</a>').show();

                    event.setParameter('redirection_timeout', paymentCompleteModalEnabled ?
                        paymentCompleteRedirectionTimeout > 0 ? paymentCompleteRedirectionTimeout : 2500 :
                        0)},
                    10
                );

                                // custom event that launches when the modal closes, allows for better control

                window.addEventListener('pay-trigger-modal-close', function (event)
                {
                    console.log('pay-trigger-modal-close');

                    eventDispatcher.dispatch(new payCryptography.StateChangeEvent(event, {
                        'state': {modalOpen: false, formSubmitted: false}
                    }), payCryptography.Events.onStateChangeEvent);

                    self.activeModal = null;

                    let isPolling = self.encryptedForm.state.isPolling();
                    let isPolling2 = self.encryptedForm.state.isFormSubmitted();

//                    console.log(isPolling2);

                    if(isPolling)
                    {
                        //console.log('Clearing polling cause of modal-exit');
                        let pol = self.encryptedForm.getPoller();
                        pol.clear();
                        self.setBitton('Place Order -cse', false);
                    } else {
                      //  console.log('Polling already stopped');
                    }

                    console.log('Calose!');

                });

                return this;
            },
            setBitton: function (tekst, setLoadingImage)
            {

                let objButton = $('#cse_place_order');

                if(objButton)
                {
                    if(setLoadingImage === true) {
                        $('#cse_place_order').addClass('tessi');
                    } else
                    {
                        objButton.removeClass('tessi');
                    }

                    objButton.html(tekst); //.prop('disabled', true);

                } else
                {
                    console.log('cant find button');
                }

            }, hideBitton: function () {

                let objButton = $('#cse_place_order');
                if (objButton) {
                    objButton.hide();
                }
            },
            initPaymentModal: function (element) {
                //console.log('initPaymentModal');
                paymentModal.createModal(element);
/*
                paymentModal.on('modalclosed', function () {

                    console.log('cant find button');
                    console.log('cant find button2');
                    console.log('cant find button3');


                });*/
            },
            initializePaymentForm: function()
            {
                console.log('test disable init');
                this.encryptedForm.init();

                //this.setBitton('gooow!');


                //eventDispatcher.dispatch(new payCryptography.StateChangeEvent(event, {'state': {formSubmitted: true}}), payCryptography.Events.onStateChangeEvent);


                ///this.activeModal = paymentModal; this.paymentModalContent('de content'); paymentModal.showModal();




                //    console.log('onERrort' );

                //   this.paymentErrorModalContent('<span>tekst</span>');
                //  errorModal.showModal();

            },
            getLanguage: function(){
                return window.checkoutConfig.payment.language[this.item.method];
            },
            getPublicEncryptionKeys: function()
            {
                if ('public_encryption_keys' in window.checkoutConfig.payment) {
                    return JSON.parse(window.checkoutConfig.payment.public_encryption_keys[this.item.method]);
                }
                return [];
            },
            prependChooseOption: function(items, key, label) {
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
            setEmptyValuesDisabled: function(option, item) {
                ko.applyBindingsToNode(option, {disable: item.value === ''}, item);
            },
            getCcMonthsValues: function ()
            {
                return this.prependChooseOption(window.checkoutConfig.payment.cc_months[this.getCode()], 'month', $t('Select'));
            },
            getCcYearsValues: function () {
                let yrs =  window.checkoutConfig.payment.cc_years[this.getCode()];
                return this.prependChooseOption(yrs, 'year', $t('Select'));
            },
            isVisible:function() {
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
            getIconSize: function(){
                var size = window.checkoutConfig.payment.iconsize;
                if(size){
                    return 'pay_icon_size_' + size;
                }
                return '';
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
            getPaymentOptionsList: function(){
                if(!this.showPaymentOptionsList){
                    return false;
                }
                if (!(this.item.method in this.paymentOptionsList)){
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
//            afterPlaceOrder: function () {
//                window.location.replace(url.build('paynl/checkout/redirect?nocache=' + (new Date().getTime())));
//            },
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
            /*
            placeOrder: function (data, event) {



                var placeOrder;

                if (event) {
                    event.preventDefault();
                }

                var objButton = $(event.target);
//                if (objButton.length > 0) {
//                    if (objButton.is('span')) {
//                        objButton = objButton.parent();
//                    }
//                    var curText = objButton.text();
//                    objButton.text($.mage.__('Processing')).prop('disabled', true);
                //}

                this.encryptedForm.state.getElementFromReference(payCryptography.Elements.form);
                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);
                $.when(placeOrder).fail(function () {
                    if (objButton.length > 0) {
                        objButton.text(curText).prop('disabled', false);
                    }
                    this.isPlaceOrderActionAllowed(true);
                }.bind(this)).done(this.afterPlaceOrder.bind(this));
                return true;
            },*/
            placeOrder: function(e){
                let self = this;
                self.setBitton($t('Processing Payment'), true);
                self.encryptedForm.state.getElementFromReference(payCryptography.Elements.form);
                self.isPlaceOrderActionAllowed(false);
                self.getPlaceOrderDeferredObject()
                    .fail(function (e) {
                            self.setBitton('Place ordert', false);
                            self.isPlaceOrderActionAllowed(true);
                        }
                    )
                    .done(function (orderId) {
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




