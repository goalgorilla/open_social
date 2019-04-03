<?php

/**
 * @file
 * Post-update hooks for the social search autocomplete module.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Fix search type not shown in suggestions for entities of type user.
 */
function social_search_autocomplete_post_update_user_search_suggestions_type() {
  // Normally loading a configuration file entirely in an update hook is bad
  // practice because its state is undefined. However, in this case the file is
  // huge with quite a few changes and this update hook will only run for sites
  // that used the pre-release version of this module.
  $config_file = drupal_get_path('module', 'social_search_autocomplete') . '/config/install/views.view.search_all_autocomplete.yml';
  $config = Yaml::parseFile($config_file);

  \Drupal::configFactory()
    ->getEditable('views.view.search_all_autocomplete')
    ->setData($config)
    ->save(TRUE);
}
