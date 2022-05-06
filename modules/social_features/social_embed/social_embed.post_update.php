<?php

/**
 * @file
 * The post update hooks for social_embed module.
 */

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
