<?php

namespace Drupal\social_group_flexible_group\Subscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class Route.
 *
 * @package Drupal\social_group_flexible_group\Subscriber
 */
class Route extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // We define our routes and how they are impacted.
    // is it on content visibility or join method.
    $join_routes = [
      'view.group_manage_members.page_group_manage_members' => 'join direct',
      'entity.group_content.add_form' => 'join added',
    ];

    foreach ($join_routes as $name => $argument) {
      if ($route = $collection->get($name)) {
        $current = $route->getRequirements();
        $requirements = array_merge($current, ['_flexible_group_join_permission' => $argument]);
        $route->addRequirements($requirements);
      }
    }

    // Based on content visibility some routes need access.
    // The argument is there for a minimum content visibility.
    $content_routes = [
      'entity.group.canonical',
      'view.group_information.page_group_about',
      'view.group_members.page_group_members',
      'social_group.stream',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
    ];

    // Invoke implementations of
    // hook_social_group_flexible_group_content_routes_alter().
    // This to ensure extensions can also add their content tabs.
    \Drupal::moduleHandler()
      ->alter('social_group_flexible_group_content_routes', $content_routes);

    foreach ($content_routes as $name) {
      if ($route = $collection->get($name)) {
        $current = $route->getRequirements();
        $requirements = array_merge($current, ['_flexible_group_content_visibility' => 'public']);
        $route->addRequirements($requirements);
      }
    }
  }

}
