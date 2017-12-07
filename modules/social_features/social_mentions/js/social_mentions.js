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

  var initMentions = function(element, context, settings, editor) {
    $(".form-textarea", element).mentionsInput({
      source: settings.path.baseUrl + "mentions-autocomplete",
      autocomplete: {
        renderItem: function(ul, item) {
          var $li = $("<li />"),
            $a = $("<a />", {
              class: "mention__item"
            }).appendTo($li);

          $a.append(item.html_item);
          return $li.appendTo(ul);
        },
        open: function(event, ui) {
          if (!CKEDITOR.instances[this.id]) {
            var menu = $(this).data("ui-mentionsAutocomplete").menu;
            menu.focus(null, $("li", menu.element).eq(0));
          }
        }
      },
      markup: function(item) {
        var type = settings.socialMentions.suggestionsFormat;

        if (type == "full_name" || (type == "all" && item.profile_id)) {
          return settings.socialMentions.prefix + item.profile_id + settings.socialMentions.suffix;
        }

        return settings.socialMentions.prefix + item.username + settings.socialMentions.suffix;
      },
      template: function(item) {
        return item.value;
      }
    });
  };

  Drupal.behaviors.socialMentions = {
    attach: function(context, settings) {
      var formIds = ".comment-form, #social-post-entity-form";

      CKEDITOR.on("instanceReady", function () {
        $(formIds).once("socialMentions").each(function (i, e) {
          initMentions(e, context, settings, CKEDITOR.instances[e.id]);
        });
      });
    }
  };

  Drupal.behaviors.socialMentionsReply = {
    attach: function (context, settings) {
      CKEDITOR.on("instanceReady", function () {
        $(".comment-form").once("socialMentionsReply").each(function (i, e) {
          var form = e,
            $textarea = $(".form-textarea", form),
            mentionsInput = $textarea.data("mentionsInput");

          $(".mention-reply").on("click", function (e) {
            e.preventDefault();

            var author = $(this).data("author"),
              empty = CKEDITOR.instances[$textarea.attr("id")] ? !CKEDITOR.instances[$textarea.attr("id")].getData().length : !$textarea.val().length;

            if (author && empty) {
              mentionsInput.handler.refreshMentions();

              if (!CKEDITOR.instances[$textarea.attr("id")]) {
                mentionsInput.handler.cache.mentions.push({
                  value: {
                    original: mentionsInput.settings.markup(author),
                    compiled: mentionsInput.settings.template(author)
                  },
                  start: {
                    original: 0,
                    compiled: 0
                  }
                });

                mentionsInput.handler.setValue($textarea.val() + author.value + " ");
                $textarea.focus();
              } else {
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
            if (!mentionsInput.handler.mentions.length) {
              $(".parent-comment", form).val("");
            }
          });
        });
      });
    }
  };

})(jQuery);
