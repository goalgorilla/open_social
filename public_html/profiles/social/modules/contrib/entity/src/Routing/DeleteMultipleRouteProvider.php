<?php

/**
 * @file
 * Contains \Drupal\entity\Routing\DeleteMultipleRouteProvider.
 */

namespace Drupal\entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides the HTML route for deleting multiple entities.
 */
class DeleteMultipleRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = new RouteCollection();
    if ($route = $this->deleteMultipleFormRoute($entity_type)) {
      $routes->add('entity.' . $entity_type->id() . '.delete_multiple_form', $route);
    }

    return $routes;
  }

  /**
   * Returns the delete multiple form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function deleteMultipleFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('delete-multiple-form')) {
      $route = new Route($entity_type->getLinkTemplate('delete-multiple-form'));
      $route->setDefault('_form', '\Drupal\entity\Form\DeleteMultiple');
      $route->setDefault('entity_type_id', $entity_type->id());
      $route->setRequirement('_permission', $entity_type->getAdminPermission());

      return $route;
    }
  }

}
