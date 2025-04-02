<?php

namespace Drupal\social_core\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_core\Controller\EntityAutocompleteController;
use Drupal\social_core\Controller\SocialCoreController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The list of methods overrides page titles.
   */
  private const CALLBACKS = [
    'system.entity_autocomplete' => EntityAutocompleteController::class . '::handleAutocomplete',
  ];

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * RouteSubscriber constructor.
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
  protected function alterRoutes(RouteCollection $collection) {
    foreach (self::CALLBACKS as $route_name => $callback) {
      if ($route = $collection->get($route_name)) {
        $route->setDefault('_controller', $callback);
      }
    }

    $titles = [];

    // Deprecate and replace the invocation of 'social_core_title'.
    $titles = $this->moduleHandler->invokeAllDeprecated(
      'Deprecated in social:13.0.0 and is removed from social:14.0.0. Use hook_social_core_add_form_title_override instead.',
      'social_core_title',
      $titles
    );

    // Deprecate and replace the invocation of 'social_core_title_alter'.
    $this->moduleHandler->alterDeprecated(
      'Deprecated in social:13.0.0 and is removed from social:14.0.0. Use hook_social_core_add_form_title_override instead.',
      'social_core_title_alter',
      $titles
    );

    if (!empty($titles['node'])) {
      if (!isset($titles['node']['bundles'])) {
        $titles['node']['bundles'] = [];
      }
    }

    // Write our own page title resolver for creation pages.
    foreach (array_column($titles, 'route_name') as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setDefault(
          '_title_callback',
          SocialCoreController::class . '::addPageTitle',
        );
      }
    }

    // New approach for overriding add_form page titles.
    $titles = $this->moduleHandler->invokeAll('social_core_add_form_title_override');
    foreach ($titles as $route_name => $data) {
      if ($route = $collection->get($route_name)) {
        if (isset($data['label'])) {
          $route->setDefault('_title_callback', SocialCoreController::class . '::generateAddFormTitle');
        }
      }
    }

    if ($route = $collection->get('system.theme_settings_theme')) {
      $route->setDefaults([
        '_title' => 'Change colors and styling',
        '_controller' => '\Drupal\social_core\Controller\ThemeController::getTheme',
      ]);
      $route->setRequirements([
        '_access' => 'TRUE',
        '_permission' => 'access social theme settings',
      ]);
    }

    // Override the permission for the site settings route.
    // Which uses administer site configuration as permission, which
    // is used in too many places and we don't want to give that out.
    if ($route = $collection->get('system.site_information_settings')) {
      $route->setRequirements([
        '_permission' => 'administer social site configuration',
      ]);
    }

    // Override the permission for the menu link route.
    // Also see social_core_menu_link_content_access().
    if ($route = $collection->get('entity.menu.add_link_form')) {
      $route->setRequirements([
        '_permission' => 'administer social menu links',
      ]);
    }
  }

}
