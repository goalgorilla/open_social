<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Routing\GroupContentRouteProvider.
 */

namespace Drupal\group\Entity\Routing;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for group content.
 */
class GroupContentRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {
  
  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupContentRouteProvider.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  public function __construct(GroupContentEnablerManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = [];
    
    // Retrieve all possible routes from all installed plugins.
    foreach ($this->pluginManager->getInstalled() as $plugin_id => $plugin) {
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
