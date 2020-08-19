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
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Paynl_Payment/payment/default'
            },
            dateofbirth: null,
            showDOB: function () {
                if(this.getUserDOB().length > 0){
                    this.dateofbirth = this.getUserDOB();
                }              
                return this.getDOB() > 0;
            },
            getDOB: function () {
                return window.checkoutConfig.payment.showdob[this.item.method];
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
            getUserDOB: function () {                
                return window.checkoutConfig.payment.userdob[this.item.method];
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
                        "dob": dob_format
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
                var showingDOB = this.getDOB() == 2;
                
                if (showingDOB) {
                    if (this.dateofbirth == null || this.dateofbirth.length < 1) {
                        alert('Voer een geldig geboortedatum in.');
                        return false;
                    }
                }      

                if (event) {
                    event.preventDefault();
                }       

                $('#pay-default-button').html('Processing').attr('disabled','disabled');

                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);

                $.when(placeOrder).fail(function () {
                    this.isPlaceOrderActionAllowed(true);
                }.bind(this)).done(this.afterPlaceOrder.bind(this));

                return true;
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('/paynl/checkout/redirect?nocache='+ (new Date().getTime())));
            },          
        });
    }
);