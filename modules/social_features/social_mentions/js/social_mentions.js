/**
 * @file
 */

(function ($) {

  "use strict";

  var CKEDITOR = window.CKEDITOR || {
    on: function(event, callback) {
      callback();
    },
    instances: {}
  };

  // Render Mention Item.
  var renderMentionItem = function (ul, item) {
    var $li = $("<li />"),
      $a = $("<a />", {
        class: "mention__item"
      }).appendTo($li);

    $a.append(item.html_item);
    return $li.appendTo(ul);
  };

  // Markup for Mention Item.
  var markupMentionItem = function(item, settings) {
    var type = settings.socialMentions.suggestionsFormat;
    if (type == "full_name" || (type == "all" && item.profile_id)) {
      return settings.socialMentions.prefix + item.profile_id + settings.socialMentions.suffix;
    }
    return settings.socialMentions.prefix + item.name + settings.socialMentions.suffix;
  };

  // Adds mention input config for the textarea.
  var initMentions = function(element, context, settings) {
    $(".form-textarea", element).mentionsInput({
      source: settings.path.baseUrl + "mentions-autocomplete",
      renderItem: function(ul, item) {
        return renderMentionItem(ul, item);
      },
      markup: function(item) {
        return markupMentionItem(item, settings);
      },
      template: function(item) {
        return item.value;
      }
    });
  };

  // Initiate mentions.
  Drupal.behaviors.socialMentions = {
    attach: function(context, settings) {
      var formIds = ".comment-form, #social-post-entity-form";

      CKEDITOR.on("instanceReady", function () {
        $(formIds).once("socialMentions").each(function (i, e) {
          initMentions(e, context, settings);
        });
      });
    }
  };

  // Adds a custom behaviour for clicking on the reply button.
  Drupal.behaviors.socialMentionsReply = {
    attach: function (context, settings) {
      CKEDITOR.on("instanceReady", function () {
        $(".comment-form").once("socialMentionsReply").each(function (i, e) {
          var form = e,
            $textarea = $(".form-textarea", form),
            mentionsInput = $textarea.data("mentionsInput"),
            editor = CKEDITOR.instances[$textarea.attr("id")];

          $(".mention-reply").on("click", function (e) {
            e.preventDefault();

            var author = $(this).data("author"),
              empty = editor ? editor.getData().length : !$textarea.val().length;

            if (author && empty) {
              if (!editor) {
                $textarea.val(author.value);

                mentionsInput._updateMentions();
                mentionsInput._addMention({
                  name: author.value,
                  pos: 0,
                  uid: author.uid,
                  profile_id: author.profile_id
                });
                mentionsInput._updateValue();

                $textarea.focus();
              }
              else {
                mentionsInput.handler.editor.focus();
                mentionsInput.handler.handleInput();
                mentionsInput.handler.onSelect({}, {
                  item: author
                });
                mentionsInput.handler.editor.focus();
              }

              if (this.hash.length) {
                var pid = this.hash.substr(1);

                $(".parent-comment", form).val(pid);
              }
            }
          });

          $textarea.on("input", function () {
            // if (!mentionsInput.mentions.length) {
            //   $(".parent-comment", form).val("");
            // }
          });
        });
      });
    }
  };

})(jQuery);
