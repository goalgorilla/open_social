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

  private const HOOK_JOIN_METHOD_USAGE = 'social_group_join_method_usage';

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
    $items = $this->moduleHandler->invokeAll(self::HOOK_JOIN_METHOD_USAGE);

    $this->moduleHandler->alter(self::HOOK_JOIN_METHOD_USAGE, $items);

    if ($this->moduleHandler->moduleExists('social_group_request')) {
      $old_types = [];

      foreach ($items as $item) {
        if ($item['entity_type'] === 'group' && isset($item['bundle'])) {
          $old_types = array_merge($old_types, (array) $item['bundle']);
        }
      }

      $old_types = $new_types = array_unique($old_types);

      $this->moduleHandler->alterDeprecated(
        'Deprecated in social:11.2.0 and is removed from social:12.0.0. Use hook_social_group_join_method_usage instead. See https://www.drupal.org/node/3254715',
        'social_group_request',
        $new_types,
      );

      $added_types = $removed_types = [];

      foreach (array_unique(array_merge($old_types, $new_types)) as $type) {
        $is_new = in_array($type, $new_types);

        if (in_array($type, $old_types) !== $is_new) {
          if ($is_new) {
            $added_types[] = $type;
          }
          else {
            $removed_types[] = $type;
          }
        }
      }

      if (!empty($removed_types)) {
        foreach ($items as $item_delta => &$item) {
          if ($item['entity_type'] === 'group' && isset($item['bundle'])) {
            if (is_array($item['bundle'])) {
              foreach ($removed_types as $type) {
                $bundle_delta = array_search($type, $item['bundle']);

                if ($bundle_delta !== FALSE) {
                  unset($item['bundle'][$bundle_delta]);
                }
              }

              if (empty($item['bundle'])) {
                unset($items[$item_delta]);
              }
            }
            elseif (in_array($item['bundle'], $removed_types)) {
              unset($items[$item_delta]);
            }
          }
        }
      }

      if (!empty($added_types)) {
        $items[] = [
          'entity_type' => 'group',
          'bundle' => $added_types,
        ];
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, string $hook): void {
    $entity = $variables['elements']['#' . $hook];

    if (!$entity instanceof EntityMemberInterface) {
      return;
    }

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity->getEntityTypeId();
    $methods = [];

    foreach ($this->relations() as $data) {
      if (
        $data['entity_type'] === $entity_type_id &&
        (
          !isset($data['bundle']) &&
          $entity_type->getBundleEntityType() === NULL ||
          in_array($entity->bundle(), (array) $data['bundle'])
        )
      ) {
        if (isset($data['field'])) {
          if (isset($data['method'])) {
            $field = $entity->get($data['field']);

            if (!$field->isEmpty() && !empty($field->getValue()[0]['value'])) {
              $relation_methods = (array) $data['method'];
            }
            else {
              continue;
            }
          }
          else {
            $relation_methods = array_column(
              $entity->get($data['field'])->getValue(),
              'value',
            );
          }
        }
        else {
          $relation_methods = (array) $data['method'];
        }

        $methods = array_merge($methods, $relation_methods);
      }
    }

    if (empty($methods)) {
      return;
    }

    $definitions = array_filter(
      $this->getDefinitions(),
      fn (array $definition): bool =>
        (
          !isset($definition['entityTypeId']) ||
          $definition['entityTypeId'] === $entity_type_id
        ) &&
        (
          !isset($definition['method']) ||
          in_array($definition['method'], $methods)
        ),
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
          '#entity' => $entity,
        ];

        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasMethod(string $bundle, string $method): bool {
    foreach ($this->relations() as $relation) {
      if (
        $relation['entity_type'] === 'group' &&
        isset($relation['bundle']) &&
        in_array($bundle, (array) $relation['bundle']) &&
        (
          !isset($relation['method']) ||
          in_array($method, (array) $relation['method'])
        )
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
