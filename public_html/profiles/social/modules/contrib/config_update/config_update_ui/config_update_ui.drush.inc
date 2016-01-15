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

  $items = array();

  $items['config-list-types'] = array(
    'description' => 'List config types',
    'aliases' => array('clt'),
    'core' => array('8+'),
    'outputformat' => array(
      'default' => 'list',
    ),
  );

  $items['config-added-report'] = array(
    'description' => 'Display a list of config items that did not come from your installed modules, themes, or install profile',
    'arguments' => array(
      'name' => 'The type of config to report on. See config-list-types to list them. You can also use system.all for all types, or system.simple for simple config.',
    ),
    'required-arguments' => 1,
    'examples' => array(
      'drush config-added-report action' => 'Displays the added config report for action config.',
    ),
    'aliases' => array('cra'),
    'core' => array('8+'),
    'outputformat' => array(
      'default' => 'list',
    ),
  );

  $items['config-missing-report'] = array(
    'description' => 'Display a list of config items from your installed modules, themes, or install profile that are not currently in your active config',
    'arguments' => array(
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ),
    'required-arguments' => 2,
    'examples' => array(
      'drush config-missing-report type action' => 'Displays the missing config report for action config.',
    ),
    'aliases' => array('crm'),
    'core' => array('8+'),
    'outputformat' => array(
      'default' => 'list',
    ),
  );

  $items['config-inactive-report'] = array(
    'description' => 'Display a list of optional config items from your installed modules, themes, or install profile that are not currently in your active config',
    'arguments' => array(
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ),
    'required-arguments' => 2,
    'examples' => array(
      'drush config-inactive-report type action' => 'Displays the inactive config report for action config.',
    ),
    'aliases' => array('cri'),
    'core' => array('8+'),
    'outputformat' => array(
      'default' => 'list',
    ),
  );

  $items['config-different-report'] = array(
    'description' => 'Display a list of config items that differ from the versions imported from your installed modules, themes, or install profile. See config-diff to show what the differences are.',
    'arguments' => array(
      'type' => 'Run the report for: module, theme, profile, or "type" for config entity type.',
      'name' => 'The machine name of the module, theme, etc. to report on. See config-list-types to list types for config entities; you can also use system.all for all types, or system.simple for simple config.',
    ),
    'required-arguments' => 2,
    'examples' => array(
      'drush config-different-report type action' => 'Displays the differing config report for action config.',
    ),
    'drupal dependencies' => array('config_update_ui'),
    'aliases' => array('crd'),
    'core' => array('8+'),
    'outputformat' => array(
      'default' => 'list',
    ),
  );

  $items['config-diff'] = array(
    'description' => 'Display line-by-line differences for one config item between your active config and the version currently being provided by an installed module, theme, or install profile',
    'arguments' => array(
      'name' => 'The config item to diff. See config-different-report to list config items that are different.',
    ),
    'required-arguments' => 1,
    'examples' => array(
      'drush config-diff block.block.bartik_search' => 'Displays the config differences for the search block in the Bartik theme.',
    ),
    'aliases' => array('cfd'),
    'core' => array('8+'),
  );

  return $items;
}

/**
 * Lists available config types.
 */
function drush_config_update_ui_config_list_types() {
  $list = array();

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
  $different = array();
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
