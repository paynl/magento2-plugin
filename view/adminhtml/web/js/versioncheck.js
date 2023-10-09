require([
    'jquery'
], function (jQuery) {
    jQuery('#paynl_version_check_button').click(function () {
        new Ajax.Request(jQuery('#ajaxurl').text(), {
            loaderArea: false,
            asynchronous: true,
            onCreate: function () {
                jQuery('#paynl_version_check_result').hide();
                jQuery('#paynl_version_check_loading').css('display', 'block');
            },
            onSuccess: function (response) {
                var json = response.responseJSON;
                let result = '';

                if (!json.version) {
                    result = 'Something went wrong, please try again later'
                } else {
                    var newest_version = json.version.substring(1);
                    var current_version = jQuery('#current_version').text();

                    if (newest_version > current_version) {
                        result = 'There is a new version available (' + json.version + ')'
                    } else {
                        jQuery('#paynl_version_check_button').hide();
                        result = 'You are up to date with the latest version'
                        jQuery('#paynl_version_check_current_version').addClass('versionUpToDate');
                    }
                }

                jQuery('#paynl_version_check_result').html(result);
                jQuery('#paynl_version_check_result').css('display', 'block');
                jQuery('#paynl_version_check_loading').hide();
            }
        });
    });
});