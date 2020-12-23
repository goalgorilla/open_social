<?php

namespace Drupal\social_comment;

use Drupal\ajax_comments\Utility;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class SocialCommentLazyRenderer.
 *
 * @package Drupal\social_comment
 */
class SocialCommentLazyRenderer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * SocialCommentLazyRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * Render comments for lazy builder.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string|int $entity_id
   *   The entity id.
   * @param string $view_mode
   *   The view mode from field settings.
   * @param string $field_name
   *   The field name.
   * @param string|int|null $num_comments
   *   The number of comments.
   * @param int $pager_id
   *   Pager id to use in case of multiple pagers on the one page.
   * @param string $build_view_mode
   *   The build view mode.
   *
   * @return mixed
   *   The render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function renderComments($entity_type, $entity_id, $view_mode, $field_name, $num_comments, $pager_id, $build_view_mode = 'default') {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = $this->entityTypeManager->getStorage('comment')->loadThread($entity, $field_name, $view_mode, $num_comments, $pager_id);

    if (!$comments) {
      return [];
    }

    $build_comments = $this->entityTypeManager->getViewBuilder('comment')->viewMultiple($comments, $build_view_mode);

    if ($build_comments) {
      $build_comments['pager']['#type'] = 'pager';
      $build_comments['pager']['#route_name'] = $this->routeMatch->getRouteObject();
      $build_comments['pager']['#route_parameters'] = $this->routeMatch->getRawParameters()->all();
      if ($pager_id) {
        $build_comments['pager']['#element'] = $pager_id;
      }
    }

    // Since we are rendering it as lazy builder, make sure we attach classes
    // required by ajax_comments. In order to render reply forms etc.
    if (!empty($build_comments) && \Drupal::moduleHandler()->moduleExists('ajax_comments')) {
      Utility::addCommentClasses($build_comments);
    }

    return $build_comments;
  }

}
