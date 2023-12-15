<?php

/**
 * @file
 * The post update hooks for social_embed module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Site\Settings;

/**
 * Empty post update hook.
 */
function social_embed_post_update_11001_populate_field_embed_content_settings(array &$sandbox): void {
  // Moved to 11002.
}

/**
 * Populate the default value in new field for already existing users.
 */
function social_embed_post_update_11002_populate_field_embed_content_settings(array &$sandbox): void {
  // Even though we have provided 'field_user_embed_content_consent',
  // a default value of 1. This will only pre-fill for any newly created
  // users, but not already existing users.
  // We need populate the field value of newly added field for existing users.
  // @see social_embed_update_11002().
  $all_bundle_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');

  // Proceed only if the new field is installed.
  if (isset($all_bundle_fields['field_user_embed_content_consent'])
    && ($all_bundle_fields['field_user_embed_content_consent']->getType() === 'boolean')) {
    $database = \Drupal::database();
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    if (!isset($sandbox['progress'])) {
      // Get user ids from user table.
      $query = $database->select('users', 'u')
        ->fields('u', ['uid'])
        ->condition('uid', '0', '>')
        ->execute();

      // If 'count' is empty, we have nothing to process.
      if (!empty($query)
        && !empty($uids = $query->fetchCol())
      ) {

        // As this feature was release in Open Social 11, and because we are
        // moving this code to a new update, there can be a scenario where
        // users might have already set their preferences. So, we don't want
        // to process such uids to keep them safe.
        $existing_uids_query = $database->select('user__field_user_embed_content_consent', 'ufu')
          ->fields('ufu', ['entity_id'])
          ->condition('entity_id', '0', '>')
          ->execute();

        if (!empty($existing_uids_query)
          && !empty($existing_uids = $existing_uids_query->fetchCol())
        ) {
          $uids_diff = array_diff($uids, $existing_uids);

          // Let's store the user IDs and their count.
          $sandbox['uids'] = $uids_diff;
          $sandbox['count'] = count($uids_diff);
        }
        else {
          $sandbox['uids'] = $uids;
          $sandbox['count'] = count($uids);
        }

        if ($sandbox['count'] > 0) {
          // 'progress' will represent the current progress of our processing.
          $sandbox['progress'] = 0;
        }
        else {
          $sandbox['#finished'] = 1;
          return;
        }
      }
      else {
        $sandbox['#finished'] = 1;
        return;
      }
    }

    $step_size = Settings::get('entity_update_batch_size', 50);

    // Extract user ids for deletion per batch.
    $uids_for_adding_records = array_splice($sandbox['uids'], 0, $step_size);
    // Insert the values in table.
    foreach ($uids_for_adding_records as $uid) {
      $database->insert('user__field_user_embed_content_consent')
        ->fields(
          [
            'bundle',
            'deleted',
            'entity_id',
            'revision_id',
            'langcode',
            'delta',
            'field_user_embed_content_consent_value',
          ],
          [
            'user',
            0,
            $uid,
            $uid,
            $langcode,
            0,
            1,
          ]
        )
        ->execute();
      $sandbox['progress']++;
    }

    // Drupal’s Batch API will stop executing our update hook as soon as
    // $sandbox['#finished'] == 1 (viz., it evaluates to TRUE).
    $sandbox['#finished'] = empty($sandbox['uids']) ? 1 : $sandbox['progress'] / $sandbox['count'];
  }
}

/**
 * Remove the old social_embed button if it's not used anymore in ckeditor4.
 */
function social_embed_post_update_12001_remove_old_social_embed_button(): void {
  // Machine name of the old embed button.
  $url_embed_button = 'social_embed';

  // Get all filter formats.
  $entity_type = 'filter_format';
  $filter_formats = \Drupal::entityQuery($entity_type)->execute();

  if (!empty($filter_formats)) {
    $key = FALSE;
    // Search through the filter formats if they use the url_embed plugin.
    foreach ($filter_formats as $filter_format) {
      // Load the editor for the given filter format.
      // https://www.drupal.org/project/drupal/issues/3409040.
      $editor = editor_load($filter_format);
      // Only check for the button on non ckeditor5 instances.
      if ($editor !== NULL && $editor->getEditor() !== 'ckeditor5') {
        $key = array_recursive_search_key_map($url_embed_button, $editor->getSettings()['toolbar']);
        // No need to check other formats if the button is used.
        if ($key !== FALSE) {
          break;
        }
      }
    }
    // If we can't any instance of the url_embed,
    // we can safely remove the button.
    if ($key === FALSE) {
      // Remove the button.
      $item = \Drupal::entityTypeManager()
        ->getStorage('embed_button')
        ->load($url_embed_button);
      if ($item instanceof EntityInterface) {
        $item->delete();
      }
    }
  }
}

/**
 * Custom function to recursively search through a multidimensional array.
 */
function array_recursive_search_key_map(string $needle, array $haystack): array|bool {
  foreach ($haystack as $first_level_key => $value) {
    if ($needle === $value) {
      return [$first_level_key];
    }

    if (is_array($value)) {
      $callback = array_recursive_search_key_map($needle, $value);
      if ($callback && is_array($callback)) {
        return array_merge([$first_level_key], $callback);
      }
    }
  }
  return FALSE;
}
