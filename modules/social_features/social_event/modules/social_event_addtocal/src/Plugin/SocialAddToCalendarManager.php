<?php

namespace Drupal\social_event_addtocal\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Social add to calendar plugin manager.
 */
class SocialAddToCalendarManager extends DefaultPluginManager {

  /**
   * Constructs a new SocialAddToCalendarManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SocialAddToCalendar', $namespaces, $module_handler, 'Drupal\social_event_addtocal\Plugin\SocialAddToCalendarInterface', 'Drupal\social_event_addtocal\Annotation\SocialAddToCalendar');

    $this->alterInfo('social_add_to_calendar_info');
    $this->setCacheBackend($cache_backend, 'social_add_to_calendar_plugins');
  }

}
