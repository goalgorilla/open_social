<?php

namespace Drupal\social_content_block;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_content_block\Annotation\ContentBlock;

/**
 * Class ContentBlockManager.
 *
 * @package Drupal\social_content_block
 */
class ContentBlockManager extends DefaultPluginManager implements ContentBlockManagerInterface {

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
      'Plugin/ContentBlock',
      $namespaces,
      $module_handler,
      ContentBlockPluginInterface::class,
      ContentBlock::class
    );

    $this->alterInfo('social_content_block_info');
    $this->setCacheBackend($cache_backend, 'content_block_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getSelector($field_name, $column, $field_parents = NULL) {
    if ($field_parents !== NULL) {
      $this->fieldParents = $field_parents;
    }

    $parents = array_merge($this->fieldParents, [$field_name, 0, $column]);
    $parent = array_shift($parents);

    return ':input[name="' . $parent . '[' . implode('][', $parents) . ']"]';
  }

}
