(function ($) {

  /**
   * Behaviors.
   */
  Drupal.behaviors.socialGroupAddForm = {
    attach: function (context, settings) {

      $.fn.characterCounter = function(){
        return this.each(function(){

          var itHasLengthAttribute = $(this).attr('length') !== undefined;

          if(itHasLengthAttribute){
            $(this).on('input', updateCounter);
            $(this).on('focus', updateCounter);
            $(this).on('blur', removeCounterElement);

            addCounterElement($(this));
          }

        });
      };

      function updateCounter(){
        var maxLength     = +$(this).attr('length'),
        actualLength      = +$(this).val().length,
        isValidLength     = actualLength <= maxLength;

        $(this).parent().find('span[class="character-counter"]')
                        .html( actualLength + '/' + maxLength);

        addInputStyle(isValidLength, $(this));
      }

      function addCounterElement($input){
        var $counterElement = $('<span/>')
                            .addClass('character-counter')
                            .css('text-align','right')
                            .css('max-width','23rem')
                            .css('font-size','12px')
                            .css('height', 1);

        $input.parent().append($counterElement);
      }

      function removeCounterElement(){
        $(this).parent().find('span[class="character-counter"]').html('');
      }

      function addInputStyle(isValidLength, $input){
        var inputHasInvalidClass = $input.parent().hasClass('has-error');
        if (isValidLength && inputHasInvalidClass) {
          $input.parent().removeClass('has-error');
          $input.closest('form').find('.btn-primary').prop('disabled', false);
        }
        else if(!isValidLength && !inputHasInvalidClass){
          $input.parent().removeClass('has-success');
          $input.parent().addClass('has-error');
          $input.closest('form').find('.btn-primary').attr('disabled', true);
        }
      }

      $(document).ready(function(){
        $('input, textarea').once().characterCounter();
      });

      // DEVELOPER, PLEASE REMOVE THIS WHEN SURE WE CAN USE THE ABOVE

      // // Get "Description" textarea of Group Create form.
      // var textarea = $('#edit-field-group-description-0-value', context);
      // // Get "Save" button.
      // var submitButton = $('#edit-submit', context);
      // // Insert new counter element after textarea.
      // $('<div/>').addClass('counter').insertAfter($(textarea));
      // // Init jQuery Simply Countable plugin
      // $(textarea).simplyCountable({
      //   counter: '.counter',
      //   countType: 'characters',
      //   maxCount: 200,
      //   countDirection: 'down',
      //   safeClass: 'safe',
      //   overClass: 'over',
      //   onOverCount: function(count, countable, counter){
      //     $(submitButton).prop('disabled', true);
      //   },
      //   onSafeCount: function(count, countable, counter){
      //     $(submitButton).prop('disabled', false);
      //   },
      // });
    }
  };

})(jQuery);
