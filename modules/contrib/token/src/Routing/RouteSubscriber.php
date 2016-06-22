<?php

/**
 * @file
 * Contains \Drupal\token\Routing\RouteSubscriber.
 */

namespace Drupal\token\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Devel routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($devel_render = $entity_type->getLinkTemplate('token-devel')) {
        $options = [
          '_admin_route' => TRUE,
          '_token_entity_type_id' => $entity_type_id,
          'parameters' => [
            $entity_type_id => [
              'type' => 'entity:' . $entity_type_id,
            ],
          ],
        ];

        $route = new Route(
          $devel_render,
          [
            '_controller' => '\Drupal\token\Controller\TokenDevelController::entityTokens',
            '_title' => 'Devel Tokens',
          ],
          [
            '_permission' => 'access devel information',
            '_module_dependencies' => 'devel',
          ],
          $options
        );

        $collection->add("entity.$entity_type_id.token_devel", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', 100);
    return $events;
  }
}
