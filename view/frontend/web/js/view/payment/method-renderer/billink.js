/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Paynl_Payment/js/view/payment/method-renderer/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, Component, url, placeOrderAction, alert) {
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
            placeOrder: function (data, event) {
                var placeOrder;
                var showingKVK = this.getKVK() == 2;
                var showingDOB = this.getDOB() == 2;
                if (showingKVK) {
                    if (this.billink_agree != true) {                        
                        alert({
                            title: $.mage.__('Betalingsvoorwaarden'),
                            content: $.mage.__('U dient eerst akkoord te gaan met de betalingsvoorwaarden van Billink.'),
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
                if (showingDOB) {
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

                $('#billink-button').html('Processing').attr('disabled','disabled');

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
