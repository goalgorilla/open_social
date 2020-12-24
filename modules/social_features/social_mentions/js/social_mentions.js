/**
 * @file
 */

(function ($) {

  "use strict";

  // Get CKEditor object.
  var getCkeditor = function (){
    return window.CKEDITOR || {
      on: function(event, callback) {
        callback();
      },
      instances: {}
    };
  }

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
    $(element).mentionsInput({
      source: settings.path.baseUrl + "mentions-autocomplete",
      autocomplete: {
        renderItem: function(ul, item) {
          return renderMentionItem(ul, item);
        },
        open: function(event, ui) {
          var CKEDITOR = getCkeditor();
          if (!CKEDITOR.instances[this.id]) {

            if (window.matchMedia("(min-width: 600px)").matches) {
              var commentTextarea = $(this).offset().top + $(this).height();
              var userList = $(this).siblings(".ui-autocomplete");
              var userListHeight = $(userList).innerHeight();
              var mainHeight = $('.main-container').innerHeight();
              var documentHeight = $(document).scrollTop() + $(window).height();
              var distanceFromBottom = (documentHeight - commentTextarea);
              if ((distanceFromBottom < userListHeight) || (mainHeight < (commentTextarea + userListHeight))) {
                // class rule set bottom and top position
                // so list displays above the textarea
                $(userList).addClass("upward");
              }
            }
          }
        }
      },
      markup: function(item) {
        return markupMentionItem(item, settings);
      },
      template: function(item) {
        return item.value;
      }
    });

    // Hook up the autogrow resize event to the highligher resize event handler.
    $(element).on('autosize:resized', function () { $(element).trigger('resize.mentionsInput'); });
  };

  // Adds mention input config for the textarea.
  var initCKEditorMentions = function(element, context, settings) {
    $(element).mentionsOldInput({
      source: settings.path.baseUrl + "mentions-autocomplete",
      autocomplete: {
        renderItem: function(ul, item) {
          return renderMentionItem(ul, item);
        },
        open: function(event, ui) {
          var CKEDITOR = getCkeditor();
          if (!CKEDITOR.instances[this.id]) {
            var menu = $(this).data("ui-mentionsAutocomplete").menu;
            menu.focus(null, $("li", menu.element).eq(0));

            if (window.matchMedia("(min-width: 600px)").matches) {
              var commentTextarea = $(this).offset().top + $(this).height();
              var userList = $(this).siblings(".ui-autocomplete");
              var userListHeight = $(userList).innerHeight();
              var mainHeight = $('.main-container').innerHeight();
              var documentHeight = $(document).scrollTop() + $(window).height();
              var distanceFromBottom = (documentHeight - commentTextarea);
              if ((distanceFromBottom < userListHeight) || (mainHeight < (commentTextarea + userListHeight))) {
                // class rule set bottom and top position
                // so list displays above the textarea
                $(userList).addClass("upward");
              }
            }
          }
        }
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
      var CKEDITOR = getCkeditor();
      CKEDITOR.on("instanceReady", function () {
        $(formIds).once("socialMentions").each(function (i, element) {
          $.each($(".form-textarea", element), function (i, textarea) {
            if (typeof CKEDITOR.instances[$(textarea).attr('id')] === 'undefined') {
              initMentions(textarea, context, settings);
            }
            else {
              initCKEditorMentions(textarea, context, settings);
            }
          });
        });
      });
    }
  };

  // Adds a custom behaviour for clicking on the reply button.
  Drupal.behaviors.socialMentionsReply = {
    attach: function (context, settings) {
      var CKEDITOR = getCkeditor();
      CKEDITOR.on("instanceReady", function () {
        $(".comment-form").once("socialMentionsReply").each(function (i, e) {
          var form = e,
            $textarea = $(".form-textarea", form),
            mentionsInput = $textarea.data("mentionsInput"),
            editor = CKEDITOR.instances[$textarea.attr("id")];

          if (typeof $("[data-drupal-selector=\"comment-form\"]").offset() !== "undefined") {
            $(".comments .comment__reply-btn a").on("click", function () {
              $("html, body").animate({
                scrollTop: $("[data-drupal-selector=\"comment-form\"]").offset().top
              }, 1000);
            });
          }

          // Make sure we remove any open reply comment forms,
          // we want to add "replying" to main comment form.
          // we ensure this class is only added to reply forms in
          // socialbase/includes/form.inc.
          $(".js-comment .comment__reply-btn a").on("click", function () {
            $(".ajax-comments-form-reply").once("socialMentionsReplyFormClose").each(function (i, e) {
              $(this).parent('.comments').remove();
              $(this).remove();
            });
          });

          $(".mention-reply").on("click", function (e) {
            e.preventDefault();
            // Make sure we remove any open reply comment forms,
            // we want to add "replying" to main comment form.
            // we ensure this class is only added to reply forms in
            // socialbase/includes/form.inc.
            $(".ajax-comments-form-reply").once("socialMentionsReplyOnReplyFormClose").each(function (i, e) {
              $(this).remove();
            });

            var author = $(this).data("author");

            if (author) {
              if (!editor) {
                $textarea.val(author.value + ' ');

                mentionsInput.mentions.length = 0;
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
                if(editor.getData().length) {
                  $("[data-drupal-selector=\"comment-form\"]")
                    .find('iframe')
                    .contents()
                    .find('body')
                    .empty();
                  editor.updateElement();
                }
                mentionsInput.handler.refreshMentions();

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
            if (editor) {
              if (!mentionsInput.mentions.length) {
                $(".parent-comment", form).val("");
              }
            }
          });
        });
      });
    }
  };

})(jQuery);
