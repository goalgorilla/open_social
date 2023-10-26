(function (Drupal, $, once) {
  Drupal.behaviors.socialPostAlbumPostLoading = {
    attach: function (context) {
      $(once('socialPostAlbumPostLoading', '.social-post-album--form form', context)).each(function () {
        var postBtn = $(this).find('> .form-submit');
        var postTextField = $(this).find('.mentions-input input[type="hidden"]');

        // Add wrapper to the text inside button.
        postBtn.wrapInner('<span class="text"></span>');

        // Add load icon to the button.
        postBtn.append('<span class="post-loading"><svg class="loader" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">\n' +
          '  <circle class="internal-circle" cx="60" cy="60" r="30"></circle>\n' +
          '</svg></span>');

        // Show load icon and hide text button on click.
        postBtn.on('click', function () {
          if (postTextField.val() !== '') {
            $(this).addClass('post-loading-active');
          }
        });
      });
    }
  };
})(Drupal, jQuery, once);
