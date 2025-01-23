define([
    'jquery',
], function (jQuery) {
    var enableMultiSelect = function (element) {
        var multiSelect = jQuery('#' + element.element).parent();
        multiSelect.find('option').click(function () {
            if (jQuery(this).hasClass('selected')) {
                jQuery(this).removeClass('selected');
            } else {
                jQuery(this).addClass('selected');
            }
            var selectedValues = jQuery.map(multiSelect.find('option.selected'), function (n, i) {
                return n.value;
            }).join(',');
            multiSelect.find('input').val(selectedValues);
        });
    };
    return enableMultiSelect;
});