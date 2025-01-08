require([
    'jquery'
], function (jQuery) {

    jQuery('.multiselectPay').each(function () {
        var multiSelect = this;
        jQuery(multiSelect).find('option').click(function () {
            if (jQuery(this).hasClass('selected')) {
                jQuery(this).removeClass('selected');
            } else {
                jQuery(this).addClass('selected');
            }
            var selectedValues = jQuery.map(jQuery(multiSelect).find('option.selected'), function (n, i) {
                return n.value;
            }).join(',');
            jQuery(multiSelect).find('input').val(selectedValues);
        });
    });

});