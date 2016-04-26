<?php

/**
 * @file
 * Contains \Drupal\gnode\Routing\GroupNodeRouteProvider.
 */

namespace Drupal\gnode\Routing;

use Drupal\node\Entity\NodeType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_node group content.
 */
class GroupNodeRouteProvider {

  /**
   * Provides the shared collection route for group node plugins.
   */
  public function getRoutes() {
    $plugin_ids = $permissions_add = $permissions_create = [];
    foreach (NodeType::loadMultiple() as $name => $node_type) {
      $plugin_id = "group_node:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $name node";
    }

    $routes['entity.group_content.group_node.collection'] = new Route('group/{group}/node');
    $routes['entity.group_content.group_node.collection']
      ->setDefaults([
        '_entity_list' => 'group_content',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        'plugin_id' => $plugin_ids,
      ])
      ->setRequirement('_group_permission', 'access group_node overview')
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE)
      ->setOption('parameters', [
        'group' => ['type' => 'entity:group'],
      ]);

    $routes['entity.group_content.group_node.add_page'] = new Route('group/{group}/node/add');
    $routes['entity.group_content.group_node.add_page']
      ->setDefaults([
        '_title' => 'Add node',
        '_controller' => '\Drupal\gnode\Controller\GroupNodeController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_node.create_page'] = new Route('group/{group}/node/create');
    $routes['entity.group_content.group_node.create_page']
      ->setDefaults([
        '_title' => 'Create node',
        '_controller' => '\Drupal\gnode\Controller\GroupNodeController::createPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
