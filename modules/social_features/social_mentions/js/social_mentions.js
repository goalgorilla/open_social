/**
 * @file
 */

(function ($) {

  'use strict';

  /**
   * Behaviors.
   */
  Drupal.behaviors.socialMentions = {
    attach: function (context, settings) {
      $('.comment-form, #social-post-entity-form')
        .find('.form-textarea')
        .once('socialMentions').each(function (i, e) {
          $(e).mentionsInput({
            source: settings.path.baseUrl + 'mentions-autocomplete',
            showAtCaret: true,
            suffix: ' ',
            preview: false,
            autocomplete: {
              delay: 100,
              autofocus: false,
              renderItem: function($ul, item) {
                var $li = $('<li />'),
                    $a = $('<a class="mention__item" />').appendTo($li);

                $a.append(item.html_item);

                return $li.appendTo($ul);
              },
              open: function(event, ui) {
                var menu = $(this).data('ui-areacomplete').menu;
                menu.focus(null, $('li', menu.element).eq(0));
              }
            },
            markup: function(mention) {
              var type = settings.socialMentions.suggestionsFormat;

              if (type == 'full_name' || (type == 'all' && mention.profile_id)) {
                return '@' + mention.profile_id;
              }

              return '@' + mention.username;
            }
          });
      });
    }
  };

  Drupal.behaviors.socialMentionsReply = {
    attach: function (context, settings) {
      $('.comment-form')
        .once('socialMentionsReply')
        .each(function (i, e) {
          var form = e,
              $textarea = $('.form-textarea', form),
              mentionsInput = $textarea.data('mentionsInput');

          $('.mention-reply').on('click', function (e) {
            e.preventDefault();

            var author = $(this).data('author');

            if (author && !$textarea.val().length) {
              mentionsInput._updateMentions();
              mentionsInput._addMention({
                value: author.value,
                pos: $textarea.val().length,
                uid: author.uid,
                username: author.username,
                profile_id: author.profile_id,
                html_item: ''
              });
              mentionsInput.setValue($textarea.val() + author.value + ' ');
              mentionsInput._updateValue();
              $textarea.focus();

              if (this.hash.length) {
                var pid = this.hash.substr(1);

                $('.parent-comment', form).val(pid);
              }
            }

            return false;
          });

          $textarea.on('input', function () {
            if (!mentionsInput.mentions.length) {
              $('.parent-comment', form).val('');
            }
          });
        });
    }
  };

})(jQuery);
