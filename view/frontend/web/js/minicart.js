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
                }
                return window.fastCheckoutMinicart;
            },
            doFastcheckout: function () {
                window.location.href = '/paynl/checkout/fastcheckoutstart';
            }
        })
    }
)
