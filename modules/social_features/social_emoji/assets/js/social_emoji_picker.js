(function (Drupal, $) {

  Drupal.behaviors.socialEmojiPicker = {
    attach: function (context, settings) {

      var emojiTriggers = document.getElementsByClassName("emoji-trigger");
      var emojiPickers = document.getElementsByClassName("emoji-picker");

      function emoji_picker(event) {
        var targetElement = event.target;
        var emojiPicker = targetElement.nextSibling;
        emojiPicker.classList.toggle('shown');

        if (emojiPicker.getAttribute('listener') !== 'true') {
          emojiPicker.setAttribute('listener', true);
          emojiPicker.addEventListener(
            'emoji-click', e => {
              var parentElementId = emojiPicker.parentElement.id;
              var pos = parentElementId.lastIndexOf('-wrapper');
              var hiddenInputAttribute = parentElementId.slice(0, pos);
              var inputFieldId = parentElementId.replace('-wrapper', '-0-value');
              var textToInsert = e.detail.unicode;
              var curPos;
              var curValue;
              if (typeof CKEDITOR != "undefined" && CKEDITOR.instances[inputFieldId]) {
                CKEDITOR.instances[inputFieldId].insertText(textToInsert);
              } else {
                curPos = document.getElementById(inputFieldId).selectionStart;
                curValue = document.getElementById(inputFieldId).value;
                document.getElementById(inputFieldId).value = curValue.slice(0, curPos) + textToInsert + curValue.slice(curPos);
                var hiddenInputs = document.querySelectorAll('input[type=hidden]');
                for (var i = 0; i < hiddenInputs.length; i++) {
                  if (hiddenInputs[i].getAttribute('data-drupal-selector').includes(hiddenInputAttribute)) {
                    hiddenInputs[i].setAttribute('value', curValue.slice(0, curPos) + textToInsert + curValue.slice(curPos));
                  }
                }
              }
            }
          );
        }
      }

      for (var i = 0; i < emojiTriggers.length; i++) {
        emojiPickers[i].innerHTML = '<emoji-picker></emoji-picker>';
        emojiTriggers[i].addEventListener('click', emoji_picker);
      }

      $(document).on('click', function (e) {
        if ($(e.target).closest(".emoji-picker").length === 0 && $(e.target).closest(".emoji-trigger").length === 0) {
          $(".emoji-picker").removeClass('shown');
        }
      });

    }
  }

})(Drupal, jQuery);
