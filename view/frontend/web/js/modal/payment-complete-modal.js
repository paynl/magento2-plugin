define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return {
        modalWindow: null,

        /**
         * Create popUp window for provided element.
         *
         * @param {HTMLElement} element
         */
        createModal: function (element) {
            var options;

            this.modalWindow = element;
            options = {
                'type': 'popup',
                'modalClass': 'pay-payment-complete-modal',
                'responsive': true,
                'innerScroll': true,
                'outerClickHandler': null,
                'clickableOverlay': false,
                'title': $.mage.__('Payment received'),
                'subTitle': null,
                'closed': function() {
                    window.dispatchEvent(new Event('pay-trigger-modal-close'))
                }
            };
            modal(options, $(this.modalWindow));
        },
        showModal: function () {
            $(this.modalWindow).modal('openModal');
        },
        closeModal: function () {
            $(this.modalWindow).modal('closeModal');
        }
    };
});
