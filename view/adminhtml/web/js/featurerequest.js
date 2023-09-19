require([
  'jquery',
  'Magento_Ui/js/modal/alert'
], function (jQuery, alert) {
  jQuery('#pay_feature_email').keypress(function (e) {
    if (e.which == 13) {
      e.preventDefault()
    }
  })

  jQuery('#paynl_submit_email_feature_request').click(function () {
    var request_email = encodeURIComponent(jQuery('#pay_feature_email').val())
    var request_message = encodeURIComponent(jQuery('#pay_feature_message').val())
    var version = jQuery('#FR_payversion').text()
    var magento_version = jQuery('#FR_magentoversion').text()

    var request = 'feature_request_email=' + request_email + '&feature_request_message=' + request_message + '&pay_version=' + version + '&magento_version=' + magento_version

    jQuery('#pay_feature_email_error').css('display', 'none')
    jQuery('#pay_feature_email_error_2').css('display', 'none')
    jQuery('#pay_feature_message_error').css('display', 'none')

    var regex = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i
    if (jQuery.trim(request_message) == '' || (jQuery.trim(request_email) != '' && !regex.test(jQuery('#pay_feature_email').val()))) {
      if (jQuery.trim(request_email) != '' && !regex.test(jQuery('#pay_feature_email').val())) {
        jQuery('#pay_feature_email_error_2').css('display', 'inline')
      }
      if (jQuery.trim(request_message) == '') {
        jQuery('#pay_feature_message_error').css('display', 'inline')
      }
      return false
    }

    new Ajax.Request(jQuery('#ajaxurl').text(), {
      method: 'POST',
      parameters: request,
      loaderArea: false,
      asynchronous: true,
      onCreate: function () {
        jQuery('#paynl_submit_email_feature_request').attr('disabled', true)
      },
      onSuccess: function (response) {
        var json = response.responseJSON
        let result = ''
        if (json.result === true) {
          jQuery('#pay_feature_email').val('')
          jQuery('#pay_feature_message').val('')
          alert({
            title: jQuery('#FR_Notices_Success_title').text(),
            content: jQuery('#FR_Notices_Success_body').text()
          })
        } else {
          alert({
            title: '',
            content: ''
          })
        }
        jQuery('#paynl_submit_email_feature_request').attr('disabled', false)
      }
    })
  })
})
