define(
    [
        'jquery',
        'uiComponent'
    ],
    function ($, Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Paynl_Payment/minicart'
            },
            initialize: function () {
                this._super();
                return this;
            },
            isFastcheckoutEnabled: function () {
                if (window.fastCheckoutMinicart) {
                    $('#top-cart-btn-checkout').parent().append($('#top-cart-btn-fastcheckout'));
                } else {
                    $('#top-cart-btn-fastcheckout').remove();
                    $('#fc-modal-backdrop-minicart').remove();
                    $('#fc-modal-minicart').remove();
                }
                if (!window.fastCheckoutModalEnabled) {
                    $('#fc-modal-backdrop-minicart').remove();
                    $('#fc-modal-minicart').remove();
                }
                return window.fastCheckoutMinicart;
            },
            openFastcheckout: function () {
                if (window.fastCheckoutModalEnabled) {
                    $('#fc-modal-minicart').addClass('visible');
                    $('#fc-modal-backdrop-minicart').addClass('visible');
                    document.body.style.overflow = 'hidden';
                } else {
                    this.doFastcheckout();
                }
            },
            closeFastcheckout: function () {
                $('#fc-modal-minicart').removeClass('visible');
                $('#fc-modal-backdrop-minicart').removeClass('visible');
                document.body.style.overflow = '';
            },
            doFastcheckout: function () {
                window.location.href = '/paynl/checkout/fastcheckoutstart';
            }
        })
    }
)
