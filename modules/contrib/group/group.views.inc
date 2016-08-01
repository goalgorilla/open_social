<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function group_views_data_alter(array &$data) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_types = $entity_type_manager->getDefinitions();

  // Get the data table for GroupContent entities.
  $data_table = $entity_types['group_content']->getDataTable();

  /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
  $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');
  
  // Add views data for each installed plugin.
  foreach ($plugin_manager->getInstalled() as $plugin_id => $plugin) {
    $entity_type_id = $plugin->getEntityTypeId();
    $entity_type = $entity_types[$entity_type_id];
    $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

    // We only add one 'group_content' entry per entity type.
    if (isset($data[$entity_data_table]['group_content'])) {
      continue;
    }

    $t_args = [
      '@entity_type' => $entity_type->getLabel(),
    ];

    // This relationship will allow a content entity to easily map to the group
    // content entity that ties it to a group, optionally filtering by plugin.
    $data[$entity_data_table]['group_content'] = array(
      'title' => t('Group content for @entity_type', $t_args),
      'help' => t('Relates to the group content entities that represent the @entity_type.', $t_args),
      'relationship' => array(
        'group' => t('Group content'),
        'base' => $data_table,
        'base field' => 'entity_id',
        'relationship field' => $entity_type->getKey('id'),
        'id' => 'group_content_to_entity_reverse',
        'label' => t('@entity_type group content', $t_args),
      ),
    );
  }
}
