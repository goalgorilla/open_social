<?php

namespace Drupal\social_ajax_comments\Controller;

use Drupal\comment\CommentInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ajax_comments\Controller\AjaxCommentsController as ContribController;

/**
 * Controller routines for AJAX comments routes.
 *
 * We do not override renderCommentField(): the contrib controller removes
 * entity_type, entity, field_name, and pid from the pager's route and uses the
 * entity canonical route. Re-adding those params produced invalid pager URLs.
 */
class AjaxCommentsController extends ContribController {

  /**
   * The number of errors.
   *
   * @var int|null
   */
  protected $errors = NULL;

  /**
   * TRUE if temporary storage should be cleared.
   *
   * @var bool
   */
  protected $clearTempStore = TRUE;

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
    $selectors = $this->tempStore->getSelectors($request, TRUE);
    $wrapper_html_id = $selectors['wrapper_html_id'];
    $form_html_id = $selectors['form_html_id'];

    if ($cid != 0) {
      $prefixes = [
        // Show the hidden anchor.
        'a#comment-',

        // Show the hidden comment.
        static::getCommentSelectorPrefix(),
      ];

      foreach ($prefixes as $prefix) {
        $command = new InvokeCommand($prefix . $cid, 'show', [200, 'linear']);
        $response->addCommand($command);
      }
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
   * {@inheritdoc}
   *
   * Preserves the comment pager page so that after editing a comment the list
   * stays on the same page instead of resetting to page 1.
   */
  public function save(Request $request, CommentInterface $comment) {
    $this->preservePagerPageFromReferer($request);
    return parent::save($request, $comment);
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

    // Preserve pager page first so the current request has it before any
    // rendering (formatter reads it via PagerParameters from the request).
    $this->preservePagerPageFromReferer($request);

    // Store the selectors from the incoming request, if applicable.
    // If the selectors are not in the request, the stored ones will
    // not be overwritten.
    $this->tempStore->getSelectors($request, TRUE);

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

    $request->request->set('social_ajax_comments', TRUE);

    // Build the comment entity form.
    // This approach is very similar to the one taken in
    // \Drupal\comment\CommentLazyBuilders::renderForm().
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entityTypeManager()->getStorage('comment')->create([
      'entity_id' => $entity->id(),
      'pid' => $pid,
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => $field_name,
    ]);

    // Rebuild the form to trigger form submission.
    $form = $this->entityFormBuilder()->getForm($comment);

    // Check for errors.
    if (!($this->errors = count($this->messenger()->messagesByType('error')))) {
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
      $selectors = $this->tempStore->getSelectors($request, TRUE);
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
      if (
        !empty($cid) &&
        !$this->errors &&
        $this->currentUser()->hasPermission('skip comment approval')
      ) {
        $selector = static::getCommentSelectorPrefix() . $cid;
        $position = 'before';
      }
      // If the new comment is not to be shown immediately, or if there are
      // errors, insert the message directly below the parent comment.
      elseif ($comment->hasParentComment()) {
        $selector = static::getCommentSelectorPrefix() . $comment->getParentComment()->id();
        $position = 'after';
      }
      else {
        // If parent comment is not available insert messages to form.
        $selectors = $this->tempStore->getSelectors($request);
        $selector = $selectors['form_html_id'] ?? '';
        $position = 'before';
      }

      $response = $this->addMessages(
        $request,
        $response,
        $selector,
        $position
      );
    }

    // Clear out the tempStore variables.
    if ($this->clearTempStore) {
      $this->tempStore->deleteAll();
    }

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

  /**
   * Preserves the pager page on the request query.
   *
   * When adding a comment via AJAX, the response rebuilds the comment list. The
   * request URL is the AJAX endpoint, so it has no ?page= and the pager would
   * show page 1. We copy the page from the form POST (hidden field) or Referer.
   *
   * Note: If the user deletes the last comment on a page, the preserved page
   * may point beyond the new last page; the pager will then show an empty list.
   * Clamping would require knowing the new total page count
   * after the operation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request (controller request; pager reads from this).
   */
  protected function preservePagerPageFromReferer(Request $request): void {
    $rawPage = $this->getPagerPageFromRequest($request);
    if ($rawPage === '') {
      return;
    }
    $page = $this->normalizePagerPage($rawPage);
    $request->query->set('page', $page);
  }

  /**
   * Gets the pager page value from request (query, POST, or Referer).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   Raw page value, or empty string if not found.
   */
  private function getPagerPageFromRequest(Request $request): string {
    if ($request->query->has('page') && $request->query->get('page') !== '') {
      return (string) $request->query->get('page');
    }
    if ($request->request->has('comment_pager_page')) {
      return (string) $request->request->get('comment_pager_page');
    }
    // Check nested form data for the pager page value. ParameterBag::get()
    // returns scalar types, so use all() to access array form values.
    $all_post = $request->request->all();
    foreach (['comment_comment_form', 'comment_post_comment_form'] as $form_key) {
      $form_data = $all_post[$form_key] ?? NULL;
      if (is_array($form_data) && isset($form_data['comment_pager_page']) && (string) $form_data['comment_pager_page'] !== '') {
        return (string) $form_data['comment_pager_page'];
      }
    }
    foreach ($all_post as $value) {
      if (is_array($value) && isset($value['comment_pager_page']) && (string) $value['comment_pager_page'] !== '') {
        return (string) $value['comment_pager_page'];
      }
    }
    // Best-effort fallback: Referer is user-controlled and may be spoofed.
    $referer = $request->headers->get('Referer');
    if ($referer !== NULL && $referer !== '') {
      $parts = parse_url($referer);
      if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
        if (isset($query['page']) && is_string($query['page']) && $query['page'] !== '') {
          return $query['page'];
        }
      }
    }
    return '';
  }

  /**
   * Normalizes a pager page string to non-negative integers.
   *
   * @param string $raw
   *   Raw value from request (e.g. "1" or "0,1" for multiple pagers).
   *
   * @return string
   *   Sanitized value safe for the page query (e.g. "1" or "0,1").
   */
  private function normalizePagerPage(string $raw): string {
    $parts = array_map(static function ($p) {
      return (string) max(0, (int) $p);
    }, explode(',', $raw));
    return implode(',', $parts);
  }

  /**
   * Builds ajax response for deleting a new comment.
   *
   * This is copied from AjaxCommentsController::delete because we need to add
   * a redirect to batch page to delete comment and their dependencies.
   *
   * The only change here is the redirect to batch page is placed before
   * the return.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function socialDelete(Request $request, CommentInterface $comment): AjaxResponse {
    $response = new AjaxResponse();

    // Store the selectors from the incoming request, if applicable.
    // If the selectors are not in the request, the stored ones will
    // not be overwritten.
    $this->tempStore->getSelectors($request, $overwrite = TRUE);

    $response->addCommand(new CloseModalDialogCommand());

    // Rebuild the form to trigger form submission.
    $this->entityFormBuilder()->getForm($comment, 'delete');

    /** @var \Drupal\Core\Entity\EntityInterface $commented_entity */
    $commented_entity = $comment->getCommentedEntity();

    $this->preservePagerPageFromReferer($request);

    // Build the updated comment field and insert into a replaceWith response.
    // Also prepend any status messages in the response.
    $response = $this->buildCommentFieldResponse(
      $request,
      $response,
      $commented_entity,
      $comment->get('field_name')->value
    );

    // Calling $this->buildCommentFieldResponse() updates the stored selectors.
    $selectors = $this->tempStore->getSelectors($request);
    $wrapper_html_id = $selectors['wrapper_html_id'];

    $response = $this->addMessages(
      $request,
      $response,
      $wrapper_html_id
    );

    // Clear out the tempStore variables.
    $this->tempStore->deleteAll();

    // Get currently batch and add redirect if exist any batch.
    $batch = &batch_get();
    if ($batch) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $commented_entity */
      $commented_entity = $comment->getCommentedEntity();

      /** @var \Symfony\Component\HttpFoundation\RedirectResponse $batch_response */
      $batch_response = batch_process($commented_entity->toUrl());

      $redirect_command = new RedirectCommand($batch_response->getTargetUrl());
      $response->addCommand($redirect_command);
    }

    return $response;
  }

}
