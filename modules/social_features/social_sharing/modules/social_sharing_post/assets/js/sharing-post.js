/**
 * @file
 * Controls the popup window for copying the link.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.social_sharing_post = {
    attach: function (context, _) {
      $(document).click(function(event) {
        // Open popup when clicking on the "Share" button.
        if ($(event.target).is(".share-button")) {
          // We want to close all pop-ups that were opened and open one that we need.
          $(".modal-share").hide();
          $(event.target).next(".modal-share").show();
          event.stopPropagation();
        }

        // Close popup after clicking on the close icon.
        if ($(event.target).is(".close")) {
          $(event.target).closest(".modal-share").hide();
        }
      });

      // Gets the button that copies the link.
      var copyLink = document.getElementsByClassName('copy-link-clipboard');

      Array.from(copyLink).forEach(element => {
        element.addEventListener('click', (event) => {
          var copyText = $(event.target.parentNode.parentNode.parentNode).find('input.input-url')[0];

          copyText.select();
          copyText.setSelectionRange(0, 99999);
          document.execCommand('copy');

          var x = document.getElementById('snackbar');
          x.className = 'show';
          setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
        });
      });

      // Closes popups when we click anywhere except popup itself or share button.
      $(document).click(function(event) {
        if ($(event.target).closest(".modal-share, .share-button").length) return;

        $(".modal-share").hide();
        event.stopPropagation();
      });
    }
  }
})(jQuery);
