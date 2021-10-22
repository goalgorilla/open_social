<?php

namespace Drupal\social_comment\Service;

use Drupal\ajax_comments\Utility;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Render comments for the lazy builder.
 *
 * @package Drupal\social_comment\Service
 */
class SocialCommentLazyRenderer implements SocialCommentLazyRendererInterface {

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderComments'];
  }

  /**
   * {@inheritdoc}
   */
  public function renderComments(
    string $entity_type,
    $entity_id,
    string $view_mode,
    string $field_name,
    $num_comments,
    int $pager_id,
    string $build_view_mode = 'default',
    string $order = 'ASC',
    int $limit = 0,
    string $formatter = NULL
  ) {
    $arguments = [
      $this->entityTypeManager->getStorage($entity_type)->load($entity_id),
      $field_name,
      $view_mode,
      $num_comments,
      $pager_id,
    ];

    if ($formatter) {
      $method = 'loadFormatterThread';
      $arguments = array_merge([$formatter], $arguments, [$order, $limit]);
    }
    else {
      $method = 'loadThread';
    }

    /** @var callable $callback */
    $callback = [
      $this->entityTypeManager->getStorage('comment'),
      $method,
    ];

    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = call_user_func_array($callback, $arguments);

    if (!$comments) {
      return [];
    }

    $build_comments = $this->entityTypeManager->getViewBuilder('comment')
      ->viewMultiple($comments, $build_view_mode);

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
    if (!empty($build_comments) && $this->moduleHandler->moduleExists('ajax_comments')) {
      Utility::addCommentClasses($build_comments);
    }

    return $build_comments;
  }

}
