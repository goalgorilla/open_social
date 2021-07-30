/**
 * @file
 * Controls the popup window for copying the link.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.social_sharing_post = {
    attach: function (context, _) {
      // Get the modals.
      var modals = document.getElementsByClassName('modal-share');
      // Get the button that opens the modal.
      var btn = document.getElementsByClassName('share-button');
      // Get the <span> element that closes the modal.
      var span = document.getElementsByClassName('close');

      Array.from(btn).forEach(element => {
        element.addEventListener('click', (event) => {
          Array.from(modals).forEach(element => {
            element.style.display = 'none';
          });

          event.target.nextElementSibling.style.display = 'block';
        });
      });

      Array.from(span).forEach(element => {
        element.addEventListener('click', (event) => {
          element.closest('.modal-share').style.display = 'none';
        });
      });

      window.onclick = function (event) {
        if ($(event.target).is('.modal-share') || $(event.target).closest('.modal-share').length || $(event.target).is('.share-button')) {

        } else {
          Array.from(modals).forEach(element => {
            element.style.display = 'none';
          });
        }
      }

      // Get the button that copies the link.
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
    }
  }
})(jQuery);
