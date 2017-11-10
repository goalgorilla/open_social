<?php

namespace Drupal\social_private_message\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_private_message\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Route the old private message page to the inbox.
    if ($route = $collection->get('private_message.private_message_page')) {
      // Deny access to this route.
      $route->setRequirement('_access', 'FALSE');
    }
    // Route the private message thread page.
    if ($route = $collection->get('entity.private_message_thread.canonical')) {
      // Remove the page title.
      $route->setDefault('_title', '');
    }
  }

}
