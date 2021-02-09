<?php

namespace Drupal\social_comment\Controller;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Url;
use Drupal\comment\CommentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\comment\Controller\CommentController;

/**
 * Controller routine override to change relevant bits in the password reset.
 */
class SocialCommentController extends CommentController {

  /**
   * Redirects comment links to the correct page depending on permissions.
   *
   * @inheritdoc
   */
  public function commentPermalink(Request $request, CommentInterface $comment) {
    if ($entity = $comment->getCommentedEntity()) {
      // Check access permissions for the entity.
      /** @var \Drupal\Core\Entity\EntityBase $entity */
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      /** @var \Drupal\Core\Url $url */
      if ($url = $entity->toUrl('canonical')) {
        // Redirect the user to the correct entity.
        return $this->redirectToOriginalEntity($url, $comment, $entity);
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * Redirects to the original entity when conditions are met.
   *
   * @param \Drupal\Core\Url $url
   *   The canonical url.
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment interface.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The Entity to redirect to.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns the Redirect Response.
   */
  public function redirectToOriginalEntity(Url $url, CommentInterface $comment = NULL, EntityBase $entity = NULL) {
    $options = [];
    if (isset($comment)) {
      $options = ['fragment' => 'comment-' . $comment->id()];
    }
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $options);
  }

  /**
   * Publishes the specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to where.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function commentUnpublish(CommentInterface $comment) {
    $comment->setUnpublished();
    $comment->save();

    $this->messenger()->addStatus($this->t('Comment unpublished.'));

    if ($entity = $comment->getCommentedEntity()) {
      // Check access permissions for the entity.
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      /** @var \Drupal\Core\Url $url */
      if ($url = $entity->toUrl('canonical')) {
        // Redirect the user to the correct entity.
        return $this->redirectToOriginalEntity($url, $comment, $entity);
      }
    }

    throw new AccessDeniedHttpException();
  }

}
