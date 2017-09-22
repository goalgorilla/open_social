$(document).ready(function() {

  // Execute autoGrow method when input value is changed.
  $('.form-control--autogrow').on('input', function(){
    var scroll_height = this.scrollHeight;
    $(this).css('height', scroll_height + 'px');
  });

});