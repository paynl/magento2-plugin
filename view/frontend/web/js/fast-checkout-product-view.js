define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).click(function () {
            var form = $(element.form),
                baseUrl = form.attr('action'),
                fastCheckoutUrl = baseUrl.replace('checkout/cart/add', 'paynl/checkout/fastcheckoutproduct');
            form.attr('action', fastCheckoutUrl);
            form.trigger('submit');
            form.attr('action', baseUrl);
            return false;
        });
    }
});