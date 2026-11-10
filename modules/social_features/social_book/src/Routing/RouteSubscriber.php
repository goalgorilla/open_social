<?php

declare(strict_types=1);

namespace Drupal\social_book\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Keeps the book outline routes limited to book outline administrators.
 *
 * Core Book required the "administer book outlines" permission to change or
 * remove a node's book outline. The contributed Book module also lets anyone
 * with "add content to books" do this on any book page they can view, even
 * without permission to edit that page. Open Social grants "add content to
 * books" to verified users, so restore the stricter core requirement.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach (['entity.node.book_outline_form', 'entity.node.book_remove_form'] as $route_name) {
      $collection->get($route_name)?->setRequirement('_permission', 'administer book outlines');
    }
  }

}
