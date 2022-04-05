(function (Drupal) {

  const emojiPickerTriggers = document.getElementsByClassName("emoji-trigger");

  function emoji_picker(event)
  {
    let targetElement = event.target;
    targetElement.innerHTML = '<emoji-picker></emoji-picker>';

    if (targetElement.getAttribute('listener') !== 'true') {
      targetElement.addEventListener(
        'emoji-click', e => {
          targetElement.setAttribute('listener', true);
          let parentElementId = targetElement.parentElement.id;
          let pos = parentElementId.lastIndexOf('-wrapper');
          let hiddenInputAttribute = parentElementId.slice(0, pos);
          let inputFieldId = parentElementId.replace('-wrapper', '-0-value');
          document.getElementById(inputFieldId).focus();
          let textToInsert = e.detail.unicode;
          let curPos;
          let curValue;
          if (typeof CKEDITOR != "undefined" && CKEDITOR.instances[inputFieldId]) {
            CKEDITOR.instances[inputFieldId].insertText(textToInsert);
          }
          else
          {
            curPos = document.getElementById(inputFieldId).selectionStart;
            curValue = document.getElementById(inputFieldId).value;
            document.getElementById(inputFieldId).value = curValue.slice(0, curPos) + textToInsert + curValue.slice(curPos);
            let hiddenInputs = document.querySelectorAll('input[type=hidden]');
            for (let i = 0; i < hiddenInputs.length; i++)
            {
              if (hiddenInputs[i].getAttribute('data-drupal-selector').includes(hiddenInputAttribute)) {
                hiddenInputs[i].setAttribute('value', curValue.slice(0, curPos) + textToInsert + curValue.slice(curPos));
              }
            }
          }
        }
      );
    }
  }

  for (let i = 0; i < emojiPickerTriggers.length; i++)
  {
    emojiPickerTriggers[i].addEventListener('click', emoji_picker);
  }

  document.body.addEventListener(
    'click',
    function (event) {
      let emojiTriggers = document.getElementsByClassName('emoji-trigger');
      let isOpen = false;
      let index = 0;
      for (let i = 0; i < emojiTriggers.length; i++)
      {
        if (emojiTriggers[i].firstChild.nodeName === 'EMOJI-PICKER') {
          isOpen = true;
          index = i;
        }
      }
      if (!event.target.classList.contains('emoji-trigger') && isOpen) {
        document.getElementsByClassName('emoji-trigger')[index].innerHTML = Drupal.t("Emoji");
      }
    }
  );

})(Drupal);
