(function ($) {

  Drupal.behaviors.textareaAutogrow = {
    attach: function (context, settings) {

      $(document).ready(function() {

        // Textarea Auto Resize
        $(document).on('focus.textarea', '.js-textarea-autogrow', function(){
    			var savedValue = this.value;
    			this.value = '';
    			this.baseScrollHeight = this.scrollHeight;
    			this.value = savedValue;
    		})
    		.on('input.textarea', '.js-textarea-autogrow', function(){
    			var minRows = this.getAttribute('data-min-rows')|0,
            rows;

          this.rows = minRows;
          rows = Math.ceil((this.scrollHeight - this.baseScrollHeight) / 24);
          this.rows = minRows + rows;
        });

      }); // End of $(document).ready

    }
  }

})(jQuery);
