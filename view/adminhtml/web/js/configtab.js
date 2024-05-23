require([
  'jquery',
  'Magento_Ui/js/modal/alert'
], function ($, alert) {
  $('.obscuredDisplay').click(function () {
    $(this).parent().find('input').toggleClass('display')
  })
})
