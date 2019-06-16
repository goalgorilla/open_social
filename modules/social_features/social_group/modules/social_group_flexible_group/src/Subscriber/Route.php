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
      'view.group_members.page_group_members' => 'join direct',
      'entity.group_content.add_form' => 'join added',
    ];

    foreach ($join_routes as $name => $argument) {
      if ($route = $collection->get($name)) {
        $route->setRequirement('_flexible_group_join_permission', $argument);
      }
    }

    // Based on content visibility some routes need access.
    // The argument is there for a minimum content visibility.
    $content_routes = [
      'entity.group.canonical' => 'public',
      'view.group_information.page_group_about' => 'public',
      'social_group.stream' => 'public',
      'view.group_events.page_group_events' => 'public',
      'view.group_topics.page_group_topics' => 'public',
    ];

    foreach ($content_routes as $name => $argument) {
      if ($route = $collection->get($name)) {
        $route->setRequirement('_flexible_group_content_visibility', $argument);
      }
    }
  }

}
