<?php

namespace Drupal\social_ajax_comments\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ajax_comments\Controller\AjaxCommentsController as ContribController;

/**
 * Controller routines for AJAX comments routes.
 */
class AjaxCommentsController extends ContribController {

  /**
   * Cancel handler for the cancel form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param int $cid
   *   The id of the comment being edited, or 0 if this is a new comment.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function socialCancel(Request $request, $cid) {
    // This is based on AjaxCommentsController::cancel.
    // the only change is we have some more wrappers we need to remove,
    // we can't tell this to ajax_comments because we render it in our template
    // so instead we will just remove whatever we need.
    $response = new AjaxResponse();

    // Get the selectors.
    $selectors = $this->tempStore->getSelectors($request, $overwrite = TRUE);
    $wrapper_html_id = $selectors['wrapper_html_id'];
    $form_html_id = $selectors['form_html_id'];

    if ($cid != 0) {
      // Show the hidden anchor.
      $response->addCommand(new InvokeCommand('a#comment-' . $cid, 'show', [200, 'linear']));

      // Show the hidden comment.
      $response->addCommand(new InvokeCommand(static::getCommentSelectorPrefix() . $cid, 'show', [200, 'linear']));
    }

    // Replace the # from the form_html_id selector and add .social_ so we know
    // that we are sure we are just removing our specific form class.
    $social_form_id = str_replace('#', '.social_reply_form_wrapper_', $form_html_id);
    // Remove the form, based on $variables['comment_wrapper'] in form.inc.
    $response->addCommand(new RemoveCommand($social_form_id));

    // Remove any messages, if applicable.
    $response->addCommand(new RemoveCommand($wrapper_html_id . ' .js-ajax-comments-messages'));

    // Clear out the tempStore variables.
    $this->tempStore->deleteAll();

    return $response;
  }

  /**
   * Builds ajax response for adding a new comment without a parent comment.
   *
   * This is copied from AjaxCommentsController::add because a reply on
   * a reply is using the add new Form with a mention. While Ajax comments uses
   * the save function for a reply. This results in status message not being
   * rendered correctly.
   * The only change here is the addMessage is placed above the reply.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   *
   * @see \Drupal\comment\Controller\CommentController::getReplyForm()
   */
  public function socialAdd(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    $response = new AjaxResponse();

    // Store the selectors from the incoming request, if applicable.
    // If the selectors are not in the request, the stored ones will
    // not be overwritten.
    $this->tempStore->getSelectors($request, $overwrite = TRUE);

    // Check the user's access to reply.
    // The user should not have made it this far without proper permission,
    // but adding this access check as a fallback.
    $this->replyAccess($request, $response, $entity, $field_name, $pid);

    // If $this->replyAccess() added any commands to the AjaxResponse,
    // it means that access was denied, so we should NOT submit the form
    // and rebuild the comment field. Just return the response with the
    // error message.
    if (!empty($response->getCommands())) {
      return $response;
    }

    // Build the comment entity form.
    // This approach is very similar to the one taken in
    // \Drupal\comment\CommentLazyBuilders::renderForm().
    $comment = $this->entityTypeManager()->getStorage('comment')->create([
      'entity_id' => $entity->id(),
      'pid' => $pid,
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => $field_name,
    ]);

    // Rebuild the form to trigger form submission.
    $form = $this->entityFormBuilder()->getForm($comment);

    // Check for errors.
    if (empty(drupal_get_messages('error', FALSE))) {
      // If there are no errors, set the ajax-updated
      // selector value for the form.
      $this->tempStore->setSelector('form_html_id', $form['#attributes']['id']);

      // Build the updated comment field and insert into a replaceWith
      // response.
      $response = $this->buildCommentFieldResponse(
        $request,
        $response,
        $entity,
        $field_name
      );
    }
    else {
      // Retrieve the selector values for use in building the response.
      $selectors = $this->tempStore->getSelectors($request, $overwrite = TRUE);
      $wrapper_html_id = $selectors['wrapper_html_id'];

      // If there are errors, remove old messages.
      $response->addCommand(new RemoveCommand($wrapper_html_id . ' .js-ajax-comments-messages'));
    }

    // This ensures for a reply we will render the comment above the reply.
    if ($comment->isNew()) {
      // Retrieve the comment id of the new comment, which was saved in
      // AjaxCommentsForm::save() during the previous HTTP request.
      $cid = $this->tempStore->getCid();

      // Try to insert the message above the new comment.
      if (!empty($cid) && !$errors && \Drupal::currentUser()->hasPermission('skip comment approval')) {
        $selector = static::getCommentSelectorPrefix() . $cid;
        $response = $this->addMessages(
          $request,
          $response,
          $selector,
          'before'
        );
      }
      // If the new comment is not to be shown immediately, or if there are
      // errors, insert the message directly below the parent comment.
      else {
        $response = $this->addMessages(
          $request,
          $response,
          static::getCommentSelectorPrefix() . $comment->get('pid')->target_id,
          'after'
        );
      }
    }

    // Clear out the tempStore variables.
    $this->tempStore->deleteAll();

    // Remove the libraries from the response, otherwise when
    // core/misc/drupal.js is reinserted into the DOM, the following line of
    // code will execute, causing Drupal.attachBehaviors() to run on the entire
    // document, and reattach behaviors to DOM elements that already have them:
    // @code
    // // Attach all behaviors.
    // domready(function(){Drupal.attachBehaviors(document,drupalSettings);});
    // @endcode
    $attachments = $response->getAttachments();
    // Need to have only 'core/drupalSettings' in the asset library list.
    // If neither 'core/drupalSettings', nor a library with a dependency on it,
    // is in the list of libraries, drupalSettings will be stripped out of the
    // ajax response by \Drupal\Core\Asset\AssetResolver::getJsAssets().
    $attachments['library'] = ['core/drupalSettings'];
    // We need to keep the drupalSettings in the response, otherwise the
    // #ajax properties in the form definition won't be properly attached to
    // the rebuilt comment field returned in the ajax response, and subsequent
    // ajax interactions will be broken.
    $response->setAttachments($attachments);

    return $response;
  }

}
