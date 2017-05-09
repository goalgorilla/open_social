(function ($) {

  Drupal.behaviors.textareaAutogrow = {
    attach: function (context, settings) {

      $(document).ready(function() {

        // Initilize all textareas that need autoGrow behaviour.
        $('.js-textarea-autogrow').each(function() {

          // Define the baseScrollHeight for an empty field.
          var savedValue = this.value;
    			this.value = '';
          this.baseScrollHeight = this.scrollHeight;
    			this.value = savedValue;

          // Define the autoGrow method to be reused on certain events.
          this.autoGrow = function() {
            var minRows = this.getAttribute('data-min-rows')|0,
              rows;

            this.rows = minRows;
            rows = Math.ceil((this.scrollHeight - this.baseScrollHeight) / 24);
            this.rows = minRows + rows;
          };

          this.autoGrow();

        });

        // Execute autoGrow method when input value is changed.
        $(document).on('input.textarea', '.js-textarea-autogrow', function(){
    			this.autoGrow();
        });

      }); // End of $(document).ready

    }
  }

})(jQuery);
