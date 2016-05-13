<?php

/**
 * @file
 * Drush commands for the Configuration Update Reports module.
 */

use Drupal\Component\Diff\DiffFormatter;

/**
 * Implements hook_drush_command().
 */
function config_update_ui_drush_command() {

  $items = [];

  $items['config-list-types'] = [
    'description' => 'List config types',
    'aliases' => ['clt'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-added-report'] = [
    'description' => 'Display a list of config items that did not come from your installed modules, themes, or install profile',
    'arguments' => [
      'name' => 'The type of config to report on. See config-list-types to list them. You can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 1,
    'examples' => [
      'drush config-added-report action' => 'Displays the added config report for action config.',
    ],
    'aliases' => ['cra'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-missing-report'] = [
    'description' => 'Display a list of config items from your installed modules, themes, or install profile that are not currently in your active config',
    'arguments' => [
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-missing-report type action' => 'Displays the missing config report for action config.',
    ],
    'aliases' => ['crm'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-inactive-report'] = [
    'description' => 'Display a list of optional config items from your installed modules, themes, or install profile that are not currently in your active config',
    'arguments' => [
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-inactive-report type action' => 'Displays the inactive config report for action config.',
    ],
    'aliases' => ['cri'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-different-report'] = [
    'description' => 'Display a list of config items that differ from the versions imported from your installed modules, themes, or install profile. See config-diff to show what the differences are.',
    'arguments' => [
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ],
    'required-arguments' => 2,
    'examples' => [
      'drush config-different-report type action' => 'Displays the differing config report for action config.',
    ],
    'drupal dependencies' => ['config_update_ui'],
    'aliases' => ['crd'],
    'core' => ['8+'],
    'outputformat' => [
      'default' => 'list',
    ],
  ];

  $items['config-diff'] = [
    'description' => 'Display line-by-line differences for one config item between your active config and the version currently being provided by an installed module, theme, or install profile',
    'arguments' => [
      'name' => 'The config item to diff. See config-different-report to list config items that are different.',
    ],
    'required-arguments' => 1,
    'examples' => [
      'drush config-diff block.block.bartik_search' => 'Displays the config differences for the search block in the Bartik theme.',
    ],
    'aliases' => ['cfd'],
    'core' => ['8+'],
  ];

  return $items;
}

/**
 * Lists available config types.
 */
function drush_config_update_ui_config_list_types() {
  $list = [];

  $definitions = \Drupal::service('config_update.config_list')->listTypes();
  return array_keys($definitions);
}

/**
 * Runs the config added report.
 *
 * @param string $name
 *   Type of config to report on.
 */
function drush_config_update_ui_config_added_report($name) {
  list($active_list, $install_list, $optional_list) = \Drupal::service('config_update.config_list')->listConfig('type', $name);

  $added = array_diff($active_list, $install_list, $optional_list);

  if (!count($added)) {
    drush_print(dt('No added config'), 0, STDERR);
  }

  sort($added);
  return $added;
}

/**
 * Runs the config missing report.
 *
 * @param string $type
 *   Type of report to run: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of item to report on.
 */
function drush_config_update_ui_config_missing_report($type, $name) {
  list($active_list, $install_list, $optional_list) = \Drupal::service('config_update.config_list')->listConfig($type, $name);

  $missing = array_diff($install_list, $active_list);
  if (!count($missing)) {
    drush_print(dt('No missing config'), 0, STDERR);
  }

  sort($missing);
  return $missing;
}

/**
 * Runs the config inactive report.
 *
 * @param string $type
 *   Type of report to run: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of item to report on.
 */
function drush_config_update_ui_config_inactive_report($type, $name) {
  list($active_list, $install_list, $optional_list) = \Drupal::service('config_update.config_list')->listConfig($type, $name);

  $missing = array_diff($optional_list, $active_list);
  if (!count($missing)) {
    drush_print(dt('No inactive config'), 0, STDERR);
  }

  sort($missing);
  return $missing;
}

/**
 * Runs the config different report.
 *
 * @param string $type
 *   Type of report to run: 'type', 'module', 'theme', or 'profile'.
 * @param string $name
 *   Machine name of item to report on.
 */
function drush_config_update_ui_config_different_report($type, $name) {
  list($active_list, $install_list, $optional_list) = \Drupal::service('config_update.config_list')->listConfig($type, $name);

  $reverter = \Drupal::service('config_update.config_update');
  $differ = \Drupal::service('config_update.config_diff');

  $added = array_diff($active_list, $install_list, $optional_list);
  $both = array_diff($active_list, $added);
  $different = [];
  foreach ($both as $name) {
    $active = $reverter->getFromActive('', $name);
    $extension = $reverter->getFromExtension('', $name);
    if (!$differ->same($active, $extension)) {
      $different[] = $name;
    }
  }

  if (!count($different)) {
    drush_print(dt('No different config'), 0, STDERR);
  }

  sort($different);
  return $different;
}

/**
 * Runs the drush config-diff command.
 *
 * @param string $name
 *   Config item to diff.
 */
function drush_config_update_ui_config_diff($name) {
  $reverter = \Drupal::service('config_update.config_update');
  $differ = \Drupal::service('config_update.config_diff');
  $formatter = new DiffFormatter();

  $extension = $reverter->getFromExtension('', $name);
  $active = $reverter->getFromActive('', $name);
  if ($extension && !$active) {
    $diff = $differ->diff($extension, $active);
    drush_print($formatter->format($diff));
  }
  else {
    drush_print(dt('Config is missing, cannot diff'), 0, STDERR);
  }
}
