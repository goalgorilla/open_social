<?php

/**
 * @file
 * The post update hooks for social_embed module.
 */

use Drupal\Core\Site\Settings;

/**
 * Populate the default value in new field for already existing users.
 */
function social_embed_post_update_11001_populate_field_embed_content_settings(array &$sandbox): void {
  // Even though we have provided 'field_user_embed_content_consent',
  // a default value of 1. This will only pre-fill for any newly created
  // users, but not already existing users.
  // We need populate the field value of newly added field for existing users.
  // @see social_embed_update_11002().
  $all_bundle_fields = \Drupal::service('entity_field.manager')
    ->getFieldDefinitions('user', 'user');

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
      if (!empty($query)) {
        // If 'count' is empty, we have nothing to process.
        if (!empty($uids = $query->fetchCol())) {
          $sandbox['#finished'] = 1;
          return;
        }
        else {
          // Let's store the user IDs and their count.
          $sandbox['uids'] = $uids;
          $sandbox['count'] = count($uids);

          // 'progress' will represent the current progress of our processing.
          $sandbox['progress'] = 0;
        }
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
