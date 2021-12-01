<?php

/**
 * @file
 * The post update hooks for social_embed module.
 */

/**
 * Implements hook_post_update_NAME().
 */
function social_embed_post_update_11001_populate_field_embed_content_settings(&$sandbox) {
  // Even though we have provided 'field_user_embed_content_consent',
  // a default value of 1. This will only pre-filled for any newly created
  // users, but not already existing users.
  // We need populate the field value of newly added field for existing users.
  // @see social_embed_update_11002().
  $all_bundle_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
  // Proceed only if the new field is installed.
  if (isset($all_bundle_fields['field_user_embed_content_consent'])
    && ($all_bundle_fields['field_user_embed_content_consent']->getType() === 'boolean')) {
    $database = \Drupal::database();
    $query = $database->select('users', 'u');
    $query->fields('u', ['uid']);
    $result = $query->execute();

    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    while ($uid = $result->fetchAssoc()) {
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
            $uid['uid'],
            $uid['uid'],
            $langcode,
            0,
            1,
          ]
        )
        ->execute();
    }
  }
}
