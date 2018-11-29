/**
 * @file
 * Like and dislike icons behavior.
 */
(function ($, Drupal) {

  'use strict';

  window.likeAndDislikeService = window.likeAndDislikeService || (function() {
    function likeAndDislikeService() {}
    likeAndDislikeService.vote = function(entity_id, entity_type, tag) {
      $.ajax({
        type: "POST",
        url: drupalSettings.path.baseUrl + 'like_and_dislike/' + entity_type + '/' + tag + '/' + entity_id,
        success: function(response) {
          // Expected response is a json object where likes is the new number
          // of likes, dislikes is the new number of dislikes, message_type is
          // the type of message to display ("status" or "warning") and message
          // is the message to display.
          ['like', 'dislike'].forEach(function (iconType) {
            var selector = '#' + iconType + '-container-' + entity_type + '-' + entity_id;
            var $aTag = $(selector + ' a');
            if ($aTag.length == 0) {
              return;
            }
            response.operation[iconType] ? $aTag.addClass('voted') : $aTag.removeClass('voted');
            $(selector + ' .count').text(response[iconType + 's']);
          });

          // Display a message whether the vote was registered or an error
          // happened.
          // @todo - this will work only for case when theme has messages in
          // highlighted region.
          $('.region.region-highlighted').html("<div class='messages__wrapper layout-container'><div class='messages messages--" + response.message_type + " role='contentinfo'>" + response.message + "</div></div>");
        }
      });
    };
    return likeAndDislikeService;
  })();

})(jQuery, Drupal);
