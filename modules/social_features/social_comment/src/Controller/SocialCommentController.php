<?php

namespace Drupal\social_comment\Controller;

use Drupal\Core\Entity\Entity;
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
   * @inheritdoc
   */
  public function commentPermalink(Request $request, CommentInterface $comment) {
    if ($entity = $comment->getCommentedEntity()) {
      // Check access permissions for the entity.
      /** @var \Drupal\Core\Entity\Entity $entity */
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      /** @var \Drupal\Core\Url $url*/
      if ($url = $entity->urlInfo('canonical')) {
        // Redirect the user to the correct entity.
        return $this->redirectToOriginalEntity($url, $comment, $entity);
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * @param $url
   * @param $comment
   * @param $entity
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectToOriginalEntity(Url $url, CommentInterface $comment = NULL, Entity $entity = NULL) {
    $options = array();
    if (isset($comment)) {
      $options = array('fragment' => 'comment-' . $comment->id());
    }
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $options);
  }

}
