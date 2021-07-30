/**
 * @file
 * Controls the popup window for copying the link.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.social_sharing_post = {
    attach: function (context, _) {
      // Get the modal.
      var modal = document.getElementById('share-modal');
      // Get the button that opens the modal.
      var btn = document.getElementById('share-button');
      // Get the <span> element that closes the modal.
      var span = document.getElementsByClassName('close')[0];

      // When the user clicks on the button, open the modal.
      btn.onclick = function() {
        modal.style.display = 'block';
      }

      // When the user clicks on <span> (x), close the modal.
      span.onclick = function() {
        modal.style.display = 'none';
      }

      // When the user clicks anywhere outside of the modal, close it.
      window.onclick = function(event) {
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      }

      // Get the button that copies the link.
      var copyLink = document.getElementById('copy-link-clipboard');

      // When the user clicks on the button, copy the link.
      copyLink.onclick = function() {
        var copyText = document.getElementById('input-url');
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand('copy');

        var x = document.getElementById('snackbar');
        x.className = 'show';
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
      }

    }
  }
})(jQuery);
