<?php

/**
 * @file
 * Provides views data for the dynamic_entity_reference module.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Implements hook_field_views_data().
 */
function dynamic_entity_reference_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $entity_manager = \Drupal::entityManager();
  $labels = $entity_manager->getEntityTypeLabels(TRUE);
  $options = array_keys($labels[(string) t('Content', [], ['context' => 'Entity type group'])]);
  $settings = $field_storage->getSettings();
  // Identify all the target entity type ids that can be referenced.
  if ($settings['exclude_entity_types']) {
    $target_entity_type_ids = array_diff($options, $settings['entity_type_ids'] ?: array());
  }
  else {
    $target_entity_type_ids = array_intersect($options, $settings['entity_type_ids'] ?: array());
  }
  foreach ($data as $table_name => $table_data) {
    // Add a relationship to all the target entity types.
    foreach ($target_entity_type_ids as $target_entity_type_id) {
      $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      $entity_type = $entity_manager->getDefinition($entity_type_id);
      $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
      $field_name = $field_storage->getName();

      // Provide a relationship for the entity type with the dynamic entity
      // reference field.
      $args = array(
        '@label' => $target_entity_type->getLabel(),
        '@field_name' => $field_name,
      );
      $data[$table_name][$target_entity_type_id . '__' . $field_name]['relationship'] = array(
        'title' => t('@label referenced from @field_name', $args),
        'label' => t('@field_name: @label', $args),
        'group' => $entity_type->getLabel(),
        'help' => t('Appears in: @bundles.', array('@bundles' => implode(', ', $field_storage->getBundles()))),
        'id' => 'standard',
        'base' => $target_base_table,
        'entity type' => $target_entity_type_id,
        'base field' => $target_entity_type->getKey('id'),
        'relationship field' => $field_name . '_target_id',
        // Entity reference field only has one target type whereas dynamic
        // entity reference field can have multiple target types that is why we
        // need extra join condition on target types.
        'extra' => array(
          array(
            'left_field' => $field_name . '_target_type',
            'value' => $target_entity_type_id,
          ),
        ),
      );

      // Provide a reverse relationship for the entity type that is referenced
      // by the field.
      $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_manager->getStorage($entity_type_id)->getTableMapping();
      $args['@entity'] = $entity_type->getLabel();
      $args['@label'] = $target_entity_type->getLowercaseLabel();
      $data[$target_base_table][$pseudo_field_name]['relationship'] = array(
        'title' => t('@entity using @field_name', $args),
        'label' => t('@field_name', array('@field_name' => $field_name)),
        'group' => $target_entity_type->getLabel(),
        'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
        'id' => 'entity_reverse',
        'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
        'entity_type' => $entity_type_id,
        'base field' => $entity_type->getKey('id'),
        'field_name' => $field_name,
        'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'field field' => $field_name . '_target_id',
        // Entity reference field only has one target type whereas dynamic
        // entity reference field can have multiple target types that is why we
        // need extra join condition on target types.
        'join_extra' => array(
          array(
            'field' => $field_name . '_target_type',
            'value' => $target_entity_type_id,
          ),
          array(
            'field' => 'deleted',
            'value' => 0,
            'numeric' => TRUE,
          ),
        ),
      );
    }
  }

  return $data;
}

/**
 * Implements hook_views_data().
 *
 * Adds relationships for dynamic_entity_reference base fields.
 *
 * @todo remove this when https://www.drupal.org/node/2337515 is in.
 */
function dynamic_entity_reference_views_data() {
  $entity_manager = \Drupal::entityManager();

  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
  $entity_types = [];
  $sql_entity_types = [];
  $fields_all = [];
  // Ensure origin and target entity types are SQL.
  foreach ($entity_manager->getDefinitions() as $entity_type) {
    // \Drupal\views\EntityViewsData class only allows entities with
    // \Drupal\Core\Entity\Sql\SqlEntityStorageInterface.
    if ($entity_type->hasHandlerClass('views_data') && $entity_manager->getStorage($entity_type->id()) instanceof SqlEntityStorageInterface) {
      $sql_entity_types[$entity_type->id()] = $entity_type->id();
      // Only fieldable entities have base fields.
      if ($entity_type->isSubclassOf(FieldableEntityInterface::class)) {
        $entity_types[$entity_type->id()] = $entity_type;
        foreach ($entity_manager->getBaseFieldDefinitions($entity_type->id()) as $base_field) {
          if ($base_field->getType() == 'dynamic_entity_reference') {
            $fields_all[$entity_type->id()][] = $base_field;
          }
        }
      }
    }
  }

  $data = [];
  foreach ($fields_all as $entity_type_id => $fields) {
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $entity_manager->getStorage($entity_type_id)->getTableMapping();

    $entity_type = $entity_types[$entity_type_id];
    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    foreach ($fields as $field) {
      $field_name = $field->getName();
      $columns = $table_mapping->getColumnNames($field_name);
      $column_id = $columns['target_id'];
      $column_type = $columns['target_type'];

      // Unlimited (-1) or > 1 store field data in a dedicated table.
      $table = $field->getCardinality() == 1 ? $base_table : $entity_type->getBaseTable() . '__' . $field_name;
      // Filter out non SQL entity types because \Drupal\views\EntityViewsData
      // class only allows entities with
      // \Drupal\Core\Entity\Sql\SqlEntityStorageInterface.
      $targets = array_intersect(DynamicEntityReferenceItem::getTargetTypes($field->getSettings()), array_keys($sql_entity_types));
      foreach ($targets as $target_entity_type_id) {
        $target_entity_type = $entity_types[$target_entity_type_id];
        $target_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();

        $t_args = [
          '@origin_label' => $entity_type->getLabel(),
          '@target_label' => $target_entity_type->getLabel(),
          '@field_name' => $field->getLabel() ?: $field_name,
          '@type' => 'base field',
        ];

        // Relationship (Origin -> Target).
        $psuedo_field = $target_entity_type_id . '__' . $field_name;
        $data[$table][$psuedo_field]['relationship'] = [
          'title' => t('@field_name to @target_label entities', $t_args),
          'label' => t('@field_name: @target_label', $t_args),
          'group' => $entity_type->getLabel(),
          'help' => t('References to @target_label entities referenced by @field_name @type on @origin_label entities.', $t_args),
          'id' => 'standard',
          'base' => $target_table,
          'entity type' => $target_entity_type_id,
          'base field' => $target_entity_type->getKey('id'),
          'relationship field' => $column_id,
          'extra' => [
            [
              // Entity reference field only has one target type whereas dynamic
              // entity reference field can have multiple target types that is
              // why we need extra join condition on target types.
              'left_field' => $column_type,
              'value' => $target_entity_type_id,
            ],
          ],
        ];

        // Reverse Relationship (Target -> Origin).
        $psuedo_field = 'reverse__' . $entity_type_id . '__' . $field_name;
        $data[$target_table][$psuedo_field]['relationship'] = [
          'title' => t('Reverse reference to @field_name @type on @origin_label', $t_args),
          'label' => t('Reverse reference to @field_name @type on @origin_label', $t_args),
          'group' => $target_entity_type->getLabel(),
          'help' => t('Reverse reference from @target_label entities referenced by @field_name @type on @origin_label entities.', $t_args),
        ];
        // When base field cardinality is 1 then the 'base' and 'field table'
        // are same because field column(s) exist in entity base table therefore
        // we can't use entity_reverse relationship plugin.
        if ($field->getCardinality() == 1) {
          $data[$target_table][$psuedo_field]['relationship'] += [
            'id' => 'standard',
            'base' => $table,
            'entity type' => $entity_type_id,
            'base field' => $column_id,
            'field' => $target_entity_type->getKey('id'),
            'extra' => [
              [
                // Entity reference field only has one target type whereas
                // dynamic entity reference field can have multiple target types
                // that is why we need extra join condition on target types.
                'field' => $column_type,
                'value' => $target_entity_type_id,
              ],
            ],
          ];
        }
        // When base field cardinality is greater then 1 the 'base' and
        // 'field table' are not same because field column(s) exist in separate
        // table therefore we have to use entity_reverse relationship plugin.
        else {
          $data[$target_table][$psuedo_field]['relationship'] += [
            'id' => 'entity_reverse',
            'base' => $base_table,
            'entity_type' => $entity_type_id,
            'base field' => $entity_type->getKey('id'),
            'field_name' => $field_name,
            'field table' => $table,
            'field field' => $column_id,
            // Entity reference field only has one target type whereas dynamic
            // entity reference field can have multiple target types that is why
            // we need extra join condition on target types.
            'join_extra' => [
              [
                'field' => $column_type,
                'value' => $target_entity_type_id,
              ],
            ],
          ];
        }
      }
    }
  }

  return $data;
}
