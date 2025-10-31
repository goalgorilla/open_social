<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * Dynamically adds routes based on available entity types and modules.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Add conditional routes using helper methods.
    // Each method handles its own conditions independently.
    $this->addUserSegmentExportRoute($collection);

    // Future dynamic routes can be added here:
    // $this->addAnotherConditionalRoute($collection);
  }

  /**
   * Adds user segment CSV export route if the user_segments module is enabled.
   *
   * This provides loose coupling between social_user_export and the open
   * source user_segments module. The route is only registered when the
   * user_segment entity type exists, allowing both modules to remain optional
   * for each other while still providing integrated functionality when both
   * are enabled.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   */
  private function addUserSegmentExportRoute(RouteCollection $collection): void {
    // Only add the route if the user_segment entity type exists.
    if (!$this->entityTypeManager->hasDefinition('user_segment')) {
      return;
    }

    $route = new Route(
      '/admin/people/segment/{user_segment}/export/csv',
      [
        '_controller' => '\Drupal\social_user_export\Controller\UserSegmentExportController::exportCsv',
        '_title' => 'Export User Segment to CSV',
      ],
      [
        '_permission' => 'administer user_segment',
        'user_segment' => '\d+',
      ],
      [
        '_admin_route' => TRUE,
        'parameters' => [
          'user_segment' => [
            'type' => 'entity:user_segment',
          ],
        ],
      ]
    );

    $collection->add('entity.user_segment.export_csv', $route);
  }

}
