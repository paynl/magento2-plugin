define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).click(function () {
            var form = $(element.form);     
            $('#selected_estimate_shipping').val($('#co-shipping-method-form input:checked').val()); 
            $('#selected_estimate_country').val($('select[name="country_id"]').val()); 
            $('#selected_estimate_zip').val($('input[name="postcode"]').val());       
            form.trigger('submit');           
            return false;
        });
    }
});