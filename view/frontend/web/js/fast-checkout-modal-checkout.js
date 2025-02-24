define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).click(function (event) {
            event.preventDefault();

            window.location.href = window.checkout.checkoutUrl;

            return false;
        });
    }
});