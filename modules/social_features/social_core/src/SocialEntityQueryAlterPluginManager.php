<?php

declare(strict_types=1);

namespace Drupal\social_core;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_core\Attribute\SocialEntityQueryAlter;

/**
 * SocialEntityQueryAlter plugin manager.
 */
final class SocialEntityQueryAlterPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, protected LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct('Plugin/SocialEntityQueryAlter', $namespaces, $module_handler, SocialEntityQueryAlterInterface::class, SocialEntityQueryAlter::class);
    $this->alterInfo('social_entity_query_alter_info');
    $this->setCacheBackend($cache_backend, 'social_entity_query_alter_plugins');
  }

  /**
   * Returns the list of plugins instances.
   *
   * @return \Drupal\social_core\SocialEntityQueryAlterPluginBase[]
   *   The list of plugins instances.
   */
  public function loadAll(): array {
    $instances = drupal_static(__METHOD__, []);
    if (empty($instances)) {
      foreach ($this->getDefinitions() as $definition) {
        try {
          $instances[] = $this->createInstance($definition['id']);
        }
        catch (PluginException $e) {
          $this->loggerFactory->get('social_entity_query_alter')->error($e->getMessage());
        }
      }
    }

    return $instances;
  }

}
