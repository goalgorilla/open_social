<?php

namespace Drupal\social_group;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\social_group\Annotation\Join;

/**
 * Defines the join manager.
 */
class JoinManager extends DefaultPluginManager implements JoinManagerInterface {

  use ContextAwarePluginManagerTrait;

  private const HOOK = 'social_group_join_method';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/Join',
      $namespaces,
      $module_handler,
      JoinPluginInterface::class,
      Join::class,
    );

    $this->alterInfo('social_group_join_info');
    $this->setCacheBackend($cache_backend, 'join_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function relations(): array {
    $items = [];

    foreach ($this->moduleHandler->getImplementations(self::HOOK) as $module) {
      /** @var callable $function */
      $function = $module . '_' . self::HOOK;

      $items = array_merge($items, $function());
    }

    return $this->moduleHandler->alter(self::HOOK, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, string $hook): void {
    if (
      !isset($variables['elements']['#view_mode']) ||
      $variables['elements']['#view_mode'] !== 'statistic' ||
      !isset($variables['elements']['#' . $hook])
    ) {
      return;
    }

    $entity = $variables['elements']['#' . $hook];

    if (!$entity instanceof EntityMemberInterface) {
      return;
    }

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity->getEntityTypeId();
    $found = FALSE;

    foreach ($this->relations() as $data) {
      if (
        $data['entity_type_id'] === $entity_type_id &&
        (
          !isset($data['bundle']) &&
          $entity_type->getBundleEntityType() === NULL ||
          in_array($entity->bundle(), (array) $data['bundle'])
        )
      ) {
        $found = TRUE;

        break;
      }
    }

    if (!$found || !isset($data)) {
      return;
    }

    if (isset($data['method'])) {
      $methods = (array) $data['method'];
    }
    else {
      $methods = array_column($entity->get($data['field'])->getValue(), 'value');
    }

    $definitions = array_filter(
      $this->getDefinitions(),
      function ($definition) use ($entity_type_id, $methods) {
        return (
            !isset($definition['entityTypeId']) ||
            $definition['entityTypeId'] === $entity_type_id
          ) &&
          (
            !isset($definition['method']) ||
            in_array($definition['method'], $methods)
          );
      }
    );

    usort($definitions, [SortArray::class, 'sortByWeightElement']);

    foreach ($definitions as $definition) {
      /** @var \Drupal\social_group\JoinPluginInterface $plugin */
      $plugin = $this->createInstance($definition['id']);

      $items = array_map(function ($item) {
        if ($item instanceof RenderableInterface) {
          return $item->toRenderable();
        }
        elseif (is_array($item)) {
          $attributes = $item['attributes'] ?? [];

          /** @var \Drupal\Core\Url $url */
          $url = $item['url'] ?? Url::fromRoute('<none>');

          $attributes['href'] = $url->toString();

          if (!isset($item['title'])) {
            $attributes['title'] = $item['label'];
          }

          if (!isset($item['url'])) {
            $attributes['class'][] = 'disabled';
          }

          $item['attributes'] = new Attribute($attributes);
        }

        return $item;
      }, $plugin->actions($entity, $variables));

      if ($items) {
        $variables['join'] = [
          '#theme' => 'join',
          '#primary' => array_shift($items),
          '#secondaries' => $items,
        ];

        break;
      }
    }
  }

}
