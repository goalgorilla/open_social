<?php

namespace Drupal\activity_send_push_notification;

use Drupal\activity_send_push_notification\Annotation\Push;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class PushManager.
 *
 * @package Drupal\activity_send_push_notification
 */
class PushManager extends DefaultPluginManager {

  /**
   * Constructs a PushManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/Push',
      $namespaces,
      $module_handler,
      PushInterface::class,
      Push::class
    );

    $this->alterInfo('push_info');
    $this->setCacheBackend($cache_backend, 'push');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();

    uasort($definitions, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, 'weight');
    });

    return $definitions;
  }

}
