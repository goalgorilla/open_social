<?php

namespace Drupal\social_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_core\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\social_core\Controller\EntityAutocompleteController::handleAutocomplete');
    }

    // Write our own VBO update selection for validation
    // on AJAX request.
    if ($route = $collection->get('views_bulk_operations.update_selection')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] = '\Drupal\social_core\Controller\SocialCoreController::updateSelection';
      $route->setDefaults($defaults);
    }

    // Write our own page title resolver for creation pages.
    if ($route = $collection->get('node.add')) {
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_core\Controller\SocialCoreController::addPageTitle';
      $route->setDefaults($defaults);
    }

  }

}
