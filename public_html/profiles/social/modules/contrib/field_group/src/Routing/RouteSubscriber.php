<?php

/**
 * @file
 * Contains \Drupal\field_group\Routing\RouteSubscriber.
 */

namespace Drupal\field_group\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field group routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Create a delete fieldgroup route for every entity.
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      $defaults = array();
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = array();
        if (($bundle_entity_type = $entity_type->getBundleEntityType()) && $bundle_entity_type !== 'bundle') {
          $options['parameters'][$entity_type->getBundleEntityType()] = array(
            'type' => 'entity:' . $entity_type->getBundleEntityType(),
          );
        }

        $options['parameters']['field_group'] = array(
          'type' => 'field_group',
          'entity_type' => $entity_type->getBundleEntityType(),
        );

        $route = new Route(
          "$path/groups/{field_group}/delete",
          array('_form' => '\Drupal\field_group\Form\FieldGroupDeleteForm'),
          array('_permission' => 'administer ' . $entity_type_id . ' fields'),
          $options
        );
        $collection->add("field_ui.field_group_delete_$entity_type_id", $route);

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    //$events = parent::getSubscribedEvents();
    // Come after field_ui, config_translation.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -210);
    return $events;
  }

}
