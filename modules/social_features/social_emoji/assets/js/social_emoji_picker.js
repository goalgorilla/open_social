(function ($) {

  const emojiPickerTriggers = document.getElementsByClassName("emoji-trigger")

  function emoji_picker(event) {
    let targetElement = event.target;
    targetElement.innerHTML = '<emoji-picker></emoji-picker>';

    if (targetElement.getAttribute('listener') !== 'true') {
      targetElement.addEventListener('emoji-click', e => {
        targetElement.setAttribute('listener', true);
        let parentElementId = targetElement.parentElement.id;
        let pos = parentElementId.lastIndexOf('-wrapper');
        let hiddenInputAttribute = parentElementId.slice(0, pos);
        let inputFieldId = parentElementId.replace('-wrapper', '-0-value');
        $('#' + inputFieldId).focus();
        let textToInsert = e.detail.unicode;
        let curPos;
        let curValue;

        if (typeof CKEDITOR != "undefined" && CKEDITOR.instances[inputFieldId]) {
          CKEDITOR.instances[inputFieldId].insertText(textToInsert);
        } else {
          curPos = document.getElementById(inputFieldId).selectionStart;
          curValue = $('#' + inputFieldId).val();
          $('#' + inputFieldId).val(curValue.slice(0, curPos) + textToInsert + curValue.slice(curPos));
          let hiddenInputs = $('input[type=hidden]');
          for (let i = 0; i < hiddenInputs.length; i++) {
            if (hiddenInputs[i].getAttribute('data-drupal-selector').includes(hiddenInputAttribute)) {
              hiddenInputs[i].setAttribute('value', curValue.slice(0, curPos) + textToInsert + curValue.slice(curPos));
            }
          }
        }
      });
    }

  }

  for (let i = 0; i < emojiPickerTriggers.length; i++) {
    emojiPickerTriggers[i].addEventListener('click', emoji_picker);
  }

  $(document).click(function(event) {
    let target = $(event.target);

    if(!target.closest('.emoji-trigger').length
      && $('.emoji-trigger').children().first().prop('tagName') == 'EMOJI-PICKER') {
      $('.emoji-trigger').html("Emoji");
    }
  });

})(jQuery);
