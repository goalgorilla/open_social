(function ($) {

  // Drupal.behaviors.social_emoji_picker = {
  //   attach: function(context, _) {
      document.querySelector('emoji-picker')
        .addEventListener('emoji-click', event => console.log(event.detail));
    // }
  // }

})(jQuery);
