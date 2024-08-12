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
                $('#top-cart-btn-checkout').parent().append($('#top-cart-btn-fastcheckout'));
                return window.fastCheckoutMinicart;
            },
            doFastcheckout: function () {
                window.location.href = '/paynl/checkout/fastcheckoutstart';
            }
        })
    }
)
          