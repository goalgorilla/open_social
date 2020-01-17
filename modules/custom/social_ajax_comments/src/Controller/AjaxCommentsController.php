<?php

namespace Drupal\social_ajax_comments\Controller;

use Drupal\comment\CommentInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ajax_comments\Controller\AjaxCommentsController as ContribController;

/**
 * Controller routines for AJAX comments routes.
 */
class AjaxCommentsController extends ContribController {

  /**
   * Builds ajax response for unpublishing a comment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function unpublish(Request $request, CommentInterface $comment) {
    $response = new AjaxResponse();

    // Store the selectors from the incoming request, if applicable.
    // If the selectors are not in the request, the stored ones will
    // not be overwritten.
//    $test = $this->tempStore->getSelectors($request, $overwrite = TRUE);

    // Rebuild the form to trigger form submission.
    $this->tempStore->setSelector('form_html_id', 'node-topic-field-topic-comments');

    $x = 1;

    $comment->setUnpublished();

    // Build the updated comment field and insert into a replaceWith response.
    // Also prepend any status messages in the response.
    $response = $this->buildCommentFieldResponse(
      $request,
      $response,
      $comment->getCommentedEntity(),
      $comment->get('field_name')->value
    );

    // Add message to the existing comment.
    $response = $this->addMessages(
      $request,
      $response,
      static::getCommentSelectorPrefix() . $comment->id(),
      'before'
    );

    // Clear out the tempStore variables.
    $this->tempStore->deleteAll();

    return $response;
  }

}
