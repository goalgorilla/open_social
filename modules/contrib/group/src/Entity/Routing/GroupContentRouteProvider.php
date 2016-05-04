<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Routing\GroupContentRouteProvider.
 */

namespace Drupal\group\Entity\Routing;

use Drupal\group\Plugin\GroupContentEnablerHelper;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for group content.
 */
class GroupContentRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = [];

    // Retrieve all installed content enabler plugins.
    $installed = GroupContentEnablerHelper::getInstalledContentEnablerIDs();

    // Retrieve all possible routes from all installed plugins.
    foreach (GroupContentEnablerHelper::getAllContentEnablers() as $plugin_id => $plugin) {
      // Skip plugins that have not been installed anywhere.
      if (!in_array($plugin_id, $installed)) {
        continue;
      }

      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $routes = array_merge($routes, $plugin->getRoutes());
    }

    // Build a route collection containing all of the retrieved routes.
    $collection = new RouteCollection();
    foreach ($routes as $route_id => $route) {
      $collection->add($route_id, $route);
    }

    return $collection;
  }

}
