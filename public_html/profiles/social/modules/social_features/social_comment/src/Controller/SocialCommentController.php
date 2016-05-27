<?php

/**
 * @file
 * Contains \Drupal\social_comment\Controller\SocialCommentController.
 */

namespace Drupal\social_comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\comment\Controller\CommentController;
use Drupal\Component\Utility\Crypt;

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
  public function redirectToOriginalEntity(\Drupal\Core\Url $url, CommentInterface $comment = NULL, \Drupal\Core\Entity\Entity $entity = NULL) {
    $options = array();
    if (isset($comment)) {
      $options = array('fragment' => 'comment-' . $comment->id());
    }
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $options);
  }

}
