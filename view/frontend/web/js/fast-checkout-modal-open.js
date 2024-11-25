define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).click(function (event) {
            event.preventDefault();

            $('#fc-modal').addClass('visible');
            $('#fc-modal-backdrop').addClass('visible');

            document.body.style.overflow = 'hidden';

            return false;
        });
    }
});