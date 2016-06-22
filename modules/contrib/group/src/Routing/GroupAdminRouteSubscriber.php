<?php

/**
 * @file
 * Contains \Drupal\group\Routing\GroupAdminRouteSubscriber.
 */

namespace Drupal\group\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _admin_route for specific group-related routes.
 */
class GroupAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GroupAdminRouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('group.settings')->get('use_admin_theme')) {
      foreach ($collection->all() as $route) {
        if ($route->hasOption('_group_operation_route')) {
          $route->setOption('_admin_route', TRUE);
        }
      }
    }
  }

}
