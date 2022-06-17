/**
 * @file
 * Provides an emoji picker via emoji-picker-element.
 */

(function (Drupal, drupalSettings, once, Popper, tabbable, CKEDITOR) {

  /**
   * Social emoji picker object instance.
   *
   * @type {Drupal.emojiPicker}
   */
  let emojiPicker;

  // Dynamically import the emoji picker element module.
  (async () => {
    let {Picker} = await import(drupalSettings.path.baseUrl + 'libraries/emoji-picker-element/index.js');

    emojiPicker = new Drupal.emojiPicker(Picker);

    // Manully attach social emoji picker behavior.
    try {
      Drupal.behaviors.socialEmojiPicker.attach(document, drupalSettings);
    } catch (e) {
      Drupal.throwError(e);
    }

  })();

  /**
   * Attaches the emoji picker to each Ajax form element.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Initialize the main {@link Drupal.emojiPicker} object and all
   *   {@link Drupal.emojiPickerTrigger} objects from DOM elements having the
   *   `social-emoji-capable` css class.
   */
  Drupal.behaviors.socialEmojiPicker = {
    attach: function (context, settings) {

      // This code might run before emoji picker object is ready.
      if (typeof emojiPicker !== 'undefined') {
        const elements = once('social-emoji-capable-processed', 'textarea.social-emoji-capable', context);
        elements.forEach(el => {
          new Drupal.emojiPickerTrigger(el, emojiPicker);
        });
      }
    }
  };

  /**
   * Emoji picker constructor.
   *
   * Creates the emoji picker instance. The idea is to have a single instance
   * that is being dynamically attached to a trigerring element.
   *
   * @param {Picker} Picker
   *   Emoji picker element.
   *
   * @constructor
   */
  Drupal.emojiPicker = function(Picker) {

    this.createPickerElement(Picker);
    this.onEmojiClick();
    this.onKeyDown();

    this.state = Drupal.emojiPicker.STATE_HIDDEN;

    this.focusableElements = [];

    this.instances = [];
  };

  /**
   * Open state of the emoji picker.
   *
   * @const {string}
   */
  Drupal.emojiPicker.STATE_OPEN = 'open';

  /**
   * Closed (actually hidden) state of the emoji picker.
   *
   * @const {string}
   *
   * @default
   */
  Drupal.emojiPicker.STATE_HIDDEN = 'hidden';

  /**
   * Creates an emoji picker DOM element and attaches it to the document body.
   *
   * @param {Picker} Picker
   *   Emoji picker element.
   */
  Drupal.emojiPicker.prototype.createPickerElement = function(Picker) {
    this.pickerElement = new Picker();
    this.pickerElement.setAttribute('aria-modal', 'true');
    // OS doesn't support light/dark themes. Let's stick to a light one atm.
    this.pickerElement.classList.add('light');

    document.body.appendChild(this.pickerElement);
  };

  /**
   * Handle an emoji selection event.
   *
   * Upon emoji click the selected emoji will be inserted to the
   * corresponding trigger element (both plain textareas and CKEDITOR-enabled
   * ones are supported). Then the Emoji Picker will be closed.
   */
  Drupal.emojiPicker.prototype.onEmojiClick = function() {
    this.pickerElement.addEventListener('emoji-click', (e) => {
      this.trigger.insertEmoji(e.detail.unicode);
      this.hidePicker(true);
    });
  };

  /**
   * Handle a key press.
   *
   * The Emoji Picker object features a focus trap when opened: by pressing TAB
   * key (code 9) a user will be "trapped" within elements of the Emoji Picker
   * and won't be able to "leave" the picker
   *
   * Pressing ESCAPE key (code 27) will close the Emoji Picker and return
   * focus to the corresponsing input DOM element.
   */
  Drupal.emojiPicker.prototype.onKeyDown = function () {
    this.pickerElement.addEventListener('keydown', (e) => {

      // Focus trap.
      const TAB_KEY_CODE = 9;

      // Ideally, looking for tabbale elements should be done durign the init
      // process, but there is a delay between creating a picker and a moment
      // when all elements inside the picker are being created.
      //
      // Thus let's find them when a user presses TAB for the first time.
      this.focusableElements = this.focusableElements.length ? this.focusableElements : tabbable.tabbable(this.pickerElement.shadowRoot);

      const firstFocusableEl = this.focusableElements[0],
              lastFocusableEl = this.focusableElements[this.focusableElements.length - 1];

      if (this.state === Drupal.emojiPicker.STATE_OPEN && e.keyCode === TAB_KEY_CODE) {
        // Rotate focus within the picker.
        if (e.shiftKey && this.pickerElement.shadowRoot.activeElement === firstFocusableEl) {
          e.preventDefault();
          lastFocusableEl.focus();
        } else if (!e.shiftKey && this.pickerElement.shadowRoot.activeElement === lastFocusableEl) {
          e.preventDefault();
          firstFocusableEl.focus();
        }
      }

      // Close on ESCAPE.
      const ESCAPE_KEY_CODE = 27;

      if (this.state === Drupal.emojiPicker.STATE_OPEN && e.keyCode === ESCAPE_KEY_CODE) {
        this.hidePicker(true);
      }

    });
  };

  /**
   * Displays emoji picker alongside the trigger button using Popper.js magic.
   *
   * @param {Drupal.emojiPickerTrigger} pickerTrigger
   *   An emoji trigger object "trigerring" opening of the emoji picker.
   */
  Drupal.emojiPicker.prototype.showPicker = function(pickerTrigger) {
    // Hide (if any) previously opened picker.
    if (this.state === Drupal.emojiPicker.STATE_OPEN) {
      this.hidePicker();
    }

    this.trigger = pickerTrigger;

    // Popper.js will take care of placing the emoji picker.
    this.popper = Popper.createPopper(this.trigger.button, this.pickerElement, {
      onFirstUpdate: state => {
        // Focus a user to the search input for bigger screens only, as on
        // mobiles this behavior creates unpleasant scrolls.
        if (window.innerWidth >= 480 && window.innerHeight >= 480) {
          this.pickerElement.shadowRoot.querySelector('#search').focus();
        }
      }
    });

    this.bindCloseOnClickOutside();

    this.pickerElement.classList.add('open');

    this.state = Drupal.emojiPicker.STATE_OPEN;
  };

  /**
   * Click outside of the picker must close the picker.
   */
  Drupal.emojiPicker.prototype.bindCloseOnClickOutside = function() {
    document.addEventListener('click', (e) => {
      if (this.state === Drupal.emojiPicker.STATE_OPEN) {
        // Clicked ouside? Let's close the picker.
        if (e.target !== this.pickerElement || !this.pickerElement.contains(e.target)) {
          this.hidePicker();
        }
        // Clicked inside? Let's bind this handler again.
        else {
          this.bindCloseOnClickOutside();
        }
      }
    // Bind an event once and capture it for performance reasons.
    }, {capture: true, once: true});
  };

  /**
   * Closes the emoji picker by destroying the popper.
   *
   * Optionally returns focus to the triggering input DOM element.
   *
   * @param {bool} returnFocus
   *   Whether to return focus to the corresponding input DOM element or not.
   */
  Drupal.emojiPicker.prototype.hidePicker = function(returnFocus = false) {

    if (this.state === Drupal.emojiPicker.STATE_OPEN) {
      this.popper.destroy();

      if (returnFocus) {
        this.trigger.returnFocus();
      }
    }

    this.pickerElement.classList.remove('open');

    this.state = Drupal.emojiPicker.STATE_HIDDEN;
  };

  /**
   * Creates an instance of emoji picker trigger.
   *
   *
   * @param {HTMLElement} input
   *   Input DOM element emoji picker is being attached to.
   *
   * @param {Drupal.emojiPicker} emojiPicker
   *   Emoji picker object.
   *
   * @constructor
   */
  Drupal.emojiPickerTrigger = function (input, emojiPicker) {
    this.input = input;
    this.emojiPicker = emojiPicker;
    this.inputHasBeenFocused = false;

    this.createTriggerButton();
    this.bindInputFocus();
    this.bindButtonClick();
  };

  /**
   * Creates a trigger button and inserts it after the corresponding input
   * DOM element.
   */
  Drupal.emojiPickerTrigger.prototype.createTriggerButton = function() {
    this.button = document.createElement('button');
    this.button.setAttribute('type', 'button');
    this.button.innerText = Drupal.t('Pick an emoji');
    this.button.classList.add('social-emoji-trigger');

    if (this.hasEditor()) {
      this.button.classList.add('with-editor');
    }

    this.input.after(this.button);
  };

  /**
   * Checks if element has been focused by a user.
   *
   * By default, caret position is set to the start of the text, which creates
   * a weird bug when the picker is being used without touching the input
   * beforehand.
   *
   * This makes sense for plain inputs only, as CKEditor tracks this by itself.
   */
  Drupal.emojiPickerTrigger.prototype.bindInputFocus = function() {
    this.input.addEventListener('focus', e => {
      this.inputHasBeenFocused = true;
    }, {once: true});
  };

  /**
   * Opens the emoji picker upon click on the trigger button.
   */
  Drupal.emojiPickerTrigger.prototype.bindButtonClick = function() {
    this.button.addEventListener('click', (e) => {
      e.preventDefault();
      this.emojiPicker.showPicker(this);
    }, true);
  };

  /**
   * Moves caret (cursor position) to the end of the text.
   */
  Drupal.emojiPickerTrigger.prototype.moveCaretToTheEnd = function() {
    if (this.hasEditor()) {
      const range = CKEDITOR.instances[this.input.id].createRange();
      range.moveToElementEditEnd(range.root);
      CKEDITOR.instances[this.input.id].getSelection().selectRanges([range]);
    }
    else {
      const newCursorPosition = this.input.value.length;
      this.input.setSelectionRange(newCursorPosition, newCursorPosition);
    }
  };

  /**
   * Inserts the given emoji character to the corresponding input DOM element.
   *
   * This method ensures that emoji is being inserted into the right input
   * DOM element, and that caret (cursor) position is being preserved.
   *
   * @param {string} emoji
   *   The emoji character to insert (unicode).
   */
  Drupal.emojiPickerTrigger.prototype.insertEmoji = function(emoji) {
    const input = this.input;

    // Move caret (cursor position) to the end if has not been focused by a
    // user.
    if (!this.hasBeenFocused()) {
      this.moveCaretToTheEnd();
    }

    if (this.hasEditor()) {
      CKEDITOR.instances[input.id].insertText(emoji);
    }
    else {
      const currentValue = this.input.value,
            newCursorPosition = input.selectionStart + emoji.length;
      input.value = currentValue.slice(0, input.selectionStart) + emoji + currentValue.slice(input.selectionEnd);
      input.setSelectionRange(newCursorPosition, newCursorPosition);

      // Mentions.js creates a hidden element and (unfortunately) does not
      // update it when text is being changed programatically. Fix this by doing
      // so manually.
      const parent = input.parentElement;
      if (parent.classList.contains('mentions-input')) {
        parent.querySelector('input[type="hidden"]').value = input.value;
      }
    }
  };

  /**
   * Returns focus to the corresponding input DOM element.
   */
  Drupal.emojiPickerTrigger.prototype.returnFocus = function() {

    if (!this.hasBeenFocused()) {
      this.moveCaretToTheEnd();
    }

    if (this.hasEditor()) {
      CKEDITOR.instances[this.input.id].focus();
    }
    else {
      this.input.focus();
    }
  };

  /**
   * Checks if the input DOM element has an attached CKEditor.
   *
   * @return {bool}
   *   Whether the input DOM element is CKEditor-powered or not.
   */
  Drupal.emojiPickerTrigger.prototype.hasEditor = function() {
    return typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[this.input.id];
  };

  /**
   * Checks if the input DOM element has been manually focused.
   *
   * @return {bool}
   *   Whether the input DOM element has been manually focused or not.
   */
  Drupal.emojiPickerTrigger.prototype.hasBeenFocused = function() {
    if (this.hasEditor()) {
      return CKEDITOR.instances[this.input.id].getSelection().getRanges().length !== 0;
    }
    else {
      return this.inputHasBeenFocused;
    }
  };

})(Drupal, drupalSettings, once, Popper, tabbable, window.CKEDITOR);
