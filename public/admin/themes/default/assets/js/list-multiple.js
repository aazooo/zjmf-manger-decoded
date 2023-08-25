// 表格checkbox全选
$(function () {
  $('input[name="headCheckbox"]').on('change', function () {
    $('.row-checkbox').prop("checked", $(this).is(':checked'))
  });
  $('.row-checkbox').on('change', function () {
    $('input[name="headCheckbox"]').prop('checked', $('.row-checkbox').length === $('.row-checkbox:checked').length)
  });
});