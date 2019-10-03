<?php

/**
 * @file
 * Post update hooks for the Social Profile Privacy module.
 */

/**
 * Re-save the All and Users indices to add restricted name field.
 */
function social_profile_privacy_post_update_8001_restricted_name_field() {
  // If the search api module is not installed we have nothing to do.
  if (!\Drupal::moduleHandler()->moduleExists('search_api')) {
    return;
  }

  // We load all indexes, we assume there will never be hundreds of search
  // indexes which would create its own problems for a site.
  $indexes = \Drupal::entityTypeManager()
    ->getStorage('search_api_index')
    ->loadMultiple();

  /** @var \Drupal\search_api\IndexInterface $index */
  foreach ($indexes as $index) {
    // Check if the search index has profile entities as data source.
    if ($index->isValidDatasource('entity:profile')) {
      // Disable and enable the index to ensure that the RestrictedNameProcessor
      // has the chance to add the field.
      $index->disable()->save();
      $index->enable()->save();
    }
  }
}
