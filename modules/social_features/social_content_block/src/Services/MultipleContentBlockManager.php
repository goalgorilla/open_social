<?php

namespace Drupal\social_content_block\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_content_block\Annotation\MultipleContentBlock;
use Drupal\social_content_block\MultipleContentBlockPluginInterface;

/**
 * Defines the multiple content block manager.
 *
 * @package Drupal\social_content_block
 */
class MultipleContentBlockManager extends DefaultPluginManager implements MultipleContentBlockManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct(
      'Plugin/MultipleContentBlock',
      $namespaces,
      $module_handler,
      MultipleContentBlockPluginInterface::class,
      MultipleContentBlock::class
    );

    $this->alterInfo('social_multiple_content_block_info');
    $this->setCacheBackend($cache_backend, 'multiple_content_block_plugins');

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as $id => $definition) {
      // Remove definition without provided entity type or label.
      if (!isset($definition['entity_type'], $definition['label'])) {
        unset($definitions[$id]);
        continue;
      }

      // Remove definition if provided entity type does not exist in the system.
      $entity_definition = $this->entityTypeManager->getDefinition($definition['entity_type'], FALSE);
      if ($entity_definition === NULL) {
        unset($definitions[$id]);
        continue;
      }

      // Definition has bundle but provided entity can not use bundles or
      // provided bundle does not exist in the system.
      if (isset($definition['bundle']) &&
        (
          $entity_definition->getBundleEntityType() === NULL ||
          $this->entityTypeManager->getStorage($entity_definition->getBundleEntityType())
            ->load($definition['bundle']) === NULL
        )
      ) {
        unset($definitions[$id]);
      }
    }

    return $definitions;
  }

}
