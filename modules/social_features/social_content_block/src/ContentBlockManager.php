<?php

namespace Drupal\social_content_block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_content_block\Annotation\ContentBlock;

/**
 * Defines the content block manager.
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
  public function getParents(
    string $field_name,
    string $column = NULL,
    array $element = NULL,
    bool $is_field = FALSE
  ) {
    if ($element !== NULL) {
      $parents = ['#field_parents'];

      if (!$is_field) {
        $parents = array_merge([$field_name, 'widget'], $parents);
      }

      $this->fieldParents = NestedArray::getValue($element, $parents);
    }

    $parents = array_merge($this->fieldParents, [$field_name]);

    if ($column) {
      $parents = array_merge($parents, [0, $column]);
    }

    return $parents;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelector(
    string $field_name,
    string $column = NULL,
    array $element = NULL,
    bool $is_field = FALSE
  ) {
    $parents = $this->getParents($field_name, $column, $element, $is_field);

    return sprintf(
      ':input[name="%s%s"]',
      array_shift($parents),
      $parents ? '[' . implode('][', $parents) . ']' : '',
    );
  }

}
