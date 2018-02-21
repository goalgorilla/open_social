<?php

namespace Drupal\social_language\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_language\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Set custom permissions for account email & site information & menu items
    // translation pages.
    $routes = [
      'translate system information' => [
        'config_translation.item.add.system.site_information_settings',
        'config_translation.item.delete.system.site_information_settings',
        'config_translation.item.edit.system.site_information_settings',
        'config_translation.item.overview.system.site_information_settings',
      ],
      'translate account settings' => [
        'config_translation.item.add.entity.user.admin_form',
        'config_translation.item.delete.entity.user.admin_form',
        'config_translation.item.edit.entity.user.admin_form',
        'config_translation.item.overview.entity.user.admin_form',
      ],
      'translate menu_link_content' => [
        'entity.menu_link_content.content_translation_add',
        'entity.menu_link_content.content_translation_delete',
        'entity.menu_link_content.content_translation_edit',
        'entity.menu_link_content.content_translation_overview',
      ],
    ];

    // Loop through routes that need alteration.
    foreach ($routes as $permission => $route_strings) {
      foreach ($route_strings as $route_string) {
        if ($route = $collection->get($route_string)) {
          $route->setRequirements([
            '_social_language_access' => $permission,
          ]);
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Come after content_translation.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -220];
    return $events;
  }

}
