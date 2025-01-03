define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).click(function (event) {
            event.preventDefault();

            $('#fc-modal').removeClass('visible');                  
            $('#fc-modal-backdrop').removeClass('visible');      
           
            document.body.style.overflow = '';

            return false;
        });
    }
});