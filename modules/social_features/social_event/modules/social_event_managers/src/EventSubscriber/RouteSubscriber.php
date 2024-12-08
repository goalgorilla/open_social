<?php

namespace Drupal\social_event_managers\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\social_event_managers\Form\SocialEventManagersViewsBulkOperationsConfirmAction;
use Drupal\social_event_managers\Form\SocialEventManagementViewsBulkOperationsConfigureAction;

/**
 * Builds up the routes of event management forms.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs the service with DI.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
  }

  /**
   * Returns a set of route objects.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A route collection.
   */
  public function routes(): RouteCollection {
    $collection = new RouteCollection();

    if ($this->moduleHandler->moduleExists('views_bulk_operations')) {
      $route = new Route(
        '/node/{node}/all-enrollments/configure-action',
        [
          '_form' => SocialEventManagementViewsBulkOperationsConfigureAction::class,
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
        '/node/{node}/all-enrollments/confirm-action',
        [
          '_form' => SocialEventManagersViewsBulkOperationsConfirmAction::class,
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
