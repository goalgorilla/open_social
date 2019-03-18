<?php

namespace Drupal\social_event_managers\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Builds up the routes of event management forms.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
  }

  /**
   * Returns a set of route objects.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A route collection.
   */
  public function routes() {
    $collection = new RouteCollection();

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('views_bulk_operations')) {
      $route = new Route(
        '/node/{node}/manage-all-enrollments/configure-action',
        [
          '_form' => '\Drupal\social_event_managers\Form\SocialEventManagementViewsBulkOperationsConfigureAction',
          '_title' => 'Configure action',
          'view_id' => 'event_manage_enrollments',
          'display_id' => 'page_manage_enrollments',
        ],
        [
          '_views_bulk_operation_access' => 'TRUE',
        ]
      );
      $collection->add('social_event_managers.vbo.execute_configurable', $route);

      $route = new Route(
        '/node/{node}/manage-all-enrollments/add-enrollees',
        [
          '_form' => '\Drupal\social_event_managers\Form\SocialEventManagersViewsBulkOperationsConfirmAction',
          '_title' => 'Confirm action',
          'view_id' => 'event_manage_enrollments',
          'display_id' => 'page_manage_enrollments',
        ],
        [
          '_views_bulk_operation_access' => 'TRUE',
        ]
      );
      $collection->add('social_event_managers.vbo.confirm', $route);
    }

    return $collection;
  }

}
