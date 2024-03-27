require([
    'jquery'
], function (jQuery) {

    function compareVersion (version1, version2) {
        var result = false

        if (typeof version1 !== 'object') { version1 = version1.toString().split('.'); }
        if (typeof version2 !== 'object') { version2 = version2.toString().split('.'); }

        for (var i = 0;i < (Math.max(version1.length, version2.length));i++) {
            if (version1[i] == undefined) { version1[i] = 0; }
            if (version2[i] == undefined) { version2[i] = 0; }

            if (Number(version1[i]) < Number(version2[i])) {
                result = true;
                break;
            }
            if (version1[i] != version2[i]) {
                break;
            }
        }
        return (result);
    }

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

                    if (compareVersion(current_version, newest_version)) {
                        result = 'There is a new version available (' + json.version + ')';
                    } else {
                        jQuery('#paynl_version_check_button').hide();
                        result = jQuery('#VC_version_check_result_success').text();
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