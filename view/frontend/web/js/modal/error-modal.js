define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return {
        modalWindow: null,
        theTitle:'Standaard title',

        /**
         * Create popUp window for provided element.
         *
         * @param {HTMLElement} element
         */
        createModal: function (element, titel)
        {
            var options;
            this.modalWindow = element;
            options = {
                'type': 'popup',
                'modalClass': 'pay-error-modal',
                'responsive': true,
                'innerScroll': true,
                'outerClickHandler': null,
                'clickableOverlay': false,
                'buttons': [{
                    text: $t('Ok'),
                    class: '',
                    attr: {},

                    /**
                     * Default action on button click
                     */
                    click: function (event) {
                        this.closeModal(event);
                    }
                }],
                'title': $.mage.__(this.theTitle),
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
        },
        setTitle: function (title) {
            this.theTitle = title;
        }
    };
});
