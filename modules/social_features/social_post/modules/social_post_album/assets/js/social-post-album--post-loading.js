(function ($) {
  Drupal.behaviors.socialPostAlbumPostLoading = {
    attach: function () {
      var postForm = $('.social-post-album--form form');
      var postBtn = postForm.find('> .form-submit');
      var postTextField = postForm.find('.mentions-input input[type="hidden"]');

      postBtn.wrapInner('<span class="text"></span>');
      postBtn.append('<span class="post-loading"><svg class="loader" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">\n' +
        '  <circle class="internal-circle" cx="60" cy="60" r="30"></circle>\n' +
        '</svg></span>');

      postBtn.on('click', function () {
        if (postTextField.val() !== '') {
          $(this).addClass('post-loading-active');
        }
      });
    }
  };
})(jQuery);
