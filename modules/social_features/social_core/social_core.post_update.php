<?php

/**
 * @file
 * Contains post-update hooks for the Social Core module.
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;

/**
 * Enable the queue storage entity module.
 */
function social_core_post_update_8701_enable_queue_storage() {
  \Drupal::service('module_installer')->install([
    'social_queue_storage',
  ]);
}

/**
 * Enable the select2 module.
 */
function social_core_post_update_8702_enable_select2() {
  \Drupal::service('module_installer')->install([
    'select2',
  ]);
}

/**
 * Updates the node type visibility condition.
 */
function social_core_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.socialbase_pagetitleblock',
    'block.block.socialblue_pagetitleblock',
  ];

  foreach ($block_list as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);

    if ($block->get('visibility.node_type')) {
      $configuration = $block->get('visibility.node_type');
      $configuration['id'] = 'entity_bundle:node';
      $block->set('visibility.entity_bundle:node', $configuration);
      $block->clear('visibility.node_type');
      $block->save(TRUE);
    }
  }
}

/**
 * Updates the file locations for specific field storage to private.
 */
function social_core_post_update_move_file_locations(array &$sandbox): void {
  $batch_size = 50;

  $sandbox['num_processed'] = $sandbox['num_processed'] ?? 0;
  $sandbox['file_ids'] = $sandbox['file_ids'] ?? [];

  if (empty($sandbox['file_ids'])) {
    $field_storages = [
      ['entity_type' => 'block_content', 'field_name' => 'field_hero_image'],
      ['entity_type' => 'comment', 'field_name' => 'field_comment_files'],
      ['entity_type' => 'group', 'field_name' => 'field_group_image'],
      ['entity_type' => 'node', 'field_name' => 'field_book_image'],
      ['entity_type' => 'node', 'field_name' => 'field_event_image'],
      ['entity_type' => 'node', 'field_name' => 'field_files'],
      ['entity_type' => 'node', 'field_name' => 'field_page_image'],
      ['entity_type' => 'node', 'field_name' => 'field_topic_image'],
      ['entity_type' => 'post', 'field_name' => 'field_post_image'],
      ['entity_type' => 'profile', 'field_name' => 'field_profile_image'],
      ['entity_type' => 'profile', 'field_name' => 'field_profile_banner_image'],
      ['entity_type' => 'paragraph', 'field_name' => 'field_hero_image'],
      ['entity_type' => 'paragraph', 'field_name' => 'field_hero_small_image'],
    ];

    foreach ($field_storages as $field_storage) {
      // Check if the entity type exists.
      $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();
      if (!isset($entity_type_definitions[$field_storage['entity_type']])) {
        \Drupal::logger('social_core')->info('Entity type does not exist: ' . $field_storage['entity_type']);
        continue;
      }

      // Check if the field storage exists.
      $storage = FieldStorageConfig::loadByName($field_storage['entity_type'], $field_storage['field_name']);
      if (!$storage) {
        \Drupal::logger('social_core')->info('Field storage not found: ' . $field_storage['entity_type'] . '.' . $field_storage['field_name']);
        continue;
      }

      // Proceed with loading storage and get the table name.
      $storage = \Drupal::entityTypeManager()->getStorage($field_storage['entity_type']);
      $table_name = $storage->getTableMapping()->getFieldTableName($field_storage['field_name']);

      // Ensure table information is available.
      if (!$table_name) {
        \Drupal::logger('social_core')->error('Table information not available for the field: ' . $field_storage['field_name']);
        continue;
      }

      $database = \Drupal::database();
      $query = $database->select('file_managed', 'fm');
      $query->fields('fm', ['fid']);
      $query->join($table_name, 'fi', 'fi.' . $field_storage['field_name'] . '_target_id = fm.fid');
      $query->condition('fm.uri', 'public%', 'LIKE');
      $result = $query->execute();

      if ($result) {
        $fids = $result->fetchAllAssoc('fid');
        if ($fids) {
          $sandbox['file_ids'] = array_merge($sandbox['file_ids'], array_keys($fids));
        }
      }
    }

    $sandbox['total'] = !empty($sandbox['file_ids']) ? count($sandbox['file_ids']) : 0;
  }

  if (!empty($sandbox['file_ids'])) {
    $current_batch_ids = array_slice($sandbox['file_ids'], $sandbox['num_processed'], $batch_size);
    $current_batch_entities = \Drupal::entityTypeManager()->getStorage('file')->loadMultiple($current_batch_ids);

    foreach ($current_batch_entities as $file) {
      if ($file instanceof FileInterface) {
        if (!$file->getFileUri()) {
          \Drupal::logger('social_core')->error('Failed to get file uri from ID ' . $file->id());
          continue;
        }
        $directory = 'private://' . parse_url($file->getFileUri(), PHP_URL_HOST);
        $file_system = \Drupal::service('file_system');

        if ($file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
          try {
            \Drupal::service('file.repository')->move($file, $directory . '/' . $file->getFilename());
          }
          catch (Exception $e) {
            \Drupal::logger('social_core')->error('Failed to move file with ID ' . $file->id() . ': ' . $e->getMessage());
          }
        }
        else {
          \Drupal::logger('social_core')->error('Could not prepare directory: ' . $directory);
        }
        $sandbox['num_processed']++;
      }
    }
  }

  $sandbox['#finished'] = !empty($sandbox['total']) ? $sandbox['num_processed'] / $sandbox['total'] : 1;
}
