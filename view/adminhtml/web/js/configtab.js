require([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, alert, translate) {
    $('.obscuredDisplay').click(function () {
        $(this).parent().find('input').toggleClass('display')
    })

    $('#cardrefund_submit').click(function () {
        if (parseFloat($('#cardrefund_form #refund_amount').val()) == 0 || $('#cardrefund_form #refund_amount').val().length == 0) {
            alert({
                content: $.mage.__('Refund amount must be greater than 0.00') + $('#cardrefund_form #currency').val()
            })
            return false;
        }
        if (parseFloat($('#cardrefund_form #refund_amount').val()) > parseFloat($('#cardrefund_form #refund_amount').attr('max'))) {
            alert({
                content: $.mage.__('Refund amount must not exceed ') + $('#cardrefund_form #refund_amount').attr('max') + ' ' + $('#cardrefund_form #currency').val()
            })
            return false;
        }
        if ($('#cardrefund_form #paynl_terminal').val() == 0 || $('#cardrefund_form #paynl_terminal').val().length == 0) {
            alert({
                content: $.mage.__('Select a terminal')
            })
            return false;
        }
    })
})
