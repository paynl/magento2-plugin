define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var $hiddenInput = $('#' + config.element);
        var $multiSelect = $hiddenInput.closest('.multiselectPay');

        if (!$multiSelect.length) {
            return;
        }

        var updateValue = function () {
            var selectedValues = $.map($multiSelect.find('.ms_option.selected'), function (option) {
                return $(option).data('value');
            });

            $hiddenInput.val(selectedValues.join(','));
        };

        $multiSelect.on('click', '.ms_option', function () {
            $(this).toggleClass('selected');
            updateValue();
        });

        updateValue();
    };
});
