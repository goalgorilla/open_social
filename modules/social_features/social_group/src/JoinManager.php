<?php

namespace Drupal\social_group;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_group\Annotation\Join;

/**
 * Defines the join manager.
 */
class JoinManager extends DefaultPluginManager implements JoinManagerInterface {

  use ContextAwarePluginManagerTrait;

  /**
   * The field parents.
   *
   * @var array
   */
  protected $fieldParents = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Join',
      $namespaces,
      $module_handler,
      JoinPluginInterface::class,
      Join::class
    );

    $this->alterInfo('join_info');
    $this->setCacheBackend($cache_backend, 'join_plugins');
  }

}
