CRM.$(function ($) {
  // Position the acknowledgee block.
  $('.acknowledgee-section').insertAfter('#honorType');
  // Show/hide the acknowledgee block.
  function enableAcknowledgee(enable = null) {
    var selectedValue = $('input[name="soft_credit_type_id"]:checked');
    if (!enable && selectedValue.val() == 2) {
      enable = 'show';
    }
    if (enable == 'show') {
      $('.acknowledgee-section').show();
    } else {
      $('.acknowledgee-section').hide();
      $('#honorType > .form-item').each(function () {
        $(this).show();
      });
    }
  }
  // Check to show the acknowledgee every time the honoree type changes or is cleared.
  $('input[name="soft_credit_type_id"]').each(function () {
    $(this).click(function () {
      enableAcknowledgee();
    });
  });
  $('.honor_block-group .crm-i').click(function () {
    enableAcknowledgee('hide');
  });

});
