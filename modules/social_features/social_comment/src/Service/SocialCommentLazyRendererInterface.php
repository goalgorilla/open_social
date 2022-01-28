<?php

namespace Drupal\social_comment\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Defines the comment lazy render service interface.
 *
 * @package Drupal\social_comment\Service
 */
interface SocialCommentLazyRendererInterface extends TrustedCallbackInterface {

  /**
   * SocialCommentLazyRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler
  );

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
   *   (optional) The build view mode. Defaults to 'default'.
   *
   * @return mixed
   *   The render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function renderComments(
    string $entity_type,
    $entity_id,
    string $view_mode,
    string $field_name,
    $num_comments,
    int $pager_id,
    string $build_view_mode = 'default'
  );

}
