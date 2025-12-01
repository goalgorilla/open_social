<?php

namespace Drupal\social_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a helper class for social_core install and update hooks.
 */
class SocialCoreInstallHelper implements ContainerInjectionInterface {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs SocialCoreInstallHelper.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Prepares VBO actions in the content view.
   *
   * This function performs three operations:
   * 1. Removes duplicate actions (e.g. follow/unfollow added twice).
   * 2. Removes unwanted actions:
   *    - node_promote_action (Promote to front page)
   *    - node_unpromote_action (Demote from front page)
   *    - entity:save_action:node (Save content item)
   *    - node_make_sticky_action (Make content sticky)
   *    - node_make_unsticky_action (Make content not sticky)
   * 3. Renames action labels:
   *    - "Delete selected entities / translations" → "Delete selected content"
   *    - "Publish content item" → "Publish selected content"
   *    - "Unpublish content item" → "Unpublish selected content"
   */
  public function prepareContentViewVboActions(): void {
    $config = $this->configFactory->getEditable('views.view.content');
    $view = $config->getRawData();

    // Early return if the view doesn't have the expected structure.
    if (empty($view['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'])) {
      return;
    }

    $actions_to_remove = [
      'node_promote_action',
      'node_unpromote_action',
      'entity:save_action:node',
      'node_make_sticky_action',
      'node_make_unsticky_action',
    ];

    // Label overrides for specific actions.
    $label_overrides = [
      'views_bulk_operations_delete_entity' => 'Delete selected content',
      'entity:publish_action:node' => 'Publish selected content',
      'entity:unpublish_action:node' => 'Unpublish selected content',
    ];

    $actions = $view['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'];
    $seen = [];
    $cleaned_actions = [];
    $has_changes = FALSE;

    foreach ($actions as $action) {
      $id = $action['action_id'];

      // Skip unwanted actions.
      if (in_array($id, $actions_to_remove, TRUE)) {
        $has_changes = TRUE;
        continue;
      }

      // Skip duplicates.
      if (array_key_exists($id, $seen)) {
        $has_changes = TRUE;
        continue;
      }

      // Apply label overrides.
      if (array_key_exists($id, $label_overrides)) {
        $current_label = $action['preconfiguration']['label_override'] ?? '';
        if ($current_label !== $label_overrides[$id]) {
          $action['preconfiguration']['label_override'] = $label_overrides[$id];
          $has_changes = TRUE;
        }
      }

      $seen[$id] = TRUE;
      $cleaned_actions[] = $action;
    }

    // Only save if actions were actually changed.
    if ($has_changes) {
      $view['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'] = $cleaned_actions;
      $config->setData($view)->save();
    }
  }

}
