<?php

namespace Drupal\social_flexible_group_book\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Implements the RouteSubscriber class.
 *
 * @package Drupal\social_flexible_group_book\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('view.group_books.page_group_books')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = "\Drupal\social_flexible_group_book\Controller\SFGBController::booksAccess";
      $route->setRequirements($requirements);
    }
  }

}
