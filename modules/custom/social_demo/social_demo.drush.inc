<?php

/**
 * @file
 * Contains social_demo.drush.inc.
 */

use Psr\Log\LogLevel;
use Drupal\user\Entity\User;

/**
 * Implements hook_drush_command().
 */
function social_demo_drush_command() {
  social_demo_drush_require();
  $items['demo-content-add'] = [
    'description' => dt('Create demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'options' => [
      'module' => dt('The module name that provides content data.'),
      'profile' => dt('The content profile to add.'),
    ],
    'required-arguments' => TRUE,
    'aliases' => ['dca'],
  ];

  $items['demo-content-remove'] = [
    'description' => dt('Remove demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'options' => [
      'module' => dt('The module name that provides content data.'),
      'profile' => dt('The content profile to remove.'),
    ],
    'required-arguments' => TRUE,
    'aliases' => ['dcr'],
  ];

  $items['social-demo-add'] = [
    'description' => dt('Add demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'options' => [
      'module' => dt('The module name that provides content data.'),
      'profile' => dt('The content profile to add.'),
    ],
    'examples' => [
      'drush social-demo-add user topic --profile=EEA' => dt('Generates demo content for users and topics from the EEA profile.'),
    ],
    'required-arguments' => 1,
    'aliases' => ['sda'],
  ];

  $items['social-demo-remove'] = [
    'description' => dt('Remove demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'options' => [
      'module' => dt('The module name that provides content data.'),
      'profile' => dt('The content profile to remove.'),
    ],
    'examples' => [
      'drush social-demo-remove user topic --profile=EEA' => dt('Removes demo content for users and topics from the EEA profile.'),
    ],
    'required-arguments' => 1,
    'aliases' => ['sdr'],
  ];

  $items['social-demo-generate'] = [
    'description' => dt('Generate demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types with info on amount.'),
    ],
    'examples' => [
      'drush social-demo-generate user:100 topic:2000 event:500 group:100' => dt('Generates 10000 demo users and 2000 topics.'),
    ],
    'aliases' => ['sdg'],
  ];

  return $items;
}

/**
 * Creates demo content.
 */
function drush_social_demo_demo_content_add() {
  social_demo_drush_require();
  \Drupal::currentUser()->setAccount(User::load(1));
  $content_types = func_get_args();
  $profile = drush_get_option('profile', '');
  $manager = \Drupal::service('plugin.manager.demo_content');
  $plugins = $manager->createInstances($content_types);

  /** @var \Drupal\social_demo\DemoContentInterface $plugin */
  foreach ($plugins as $plugin) {
    $definition = $plugin->getPluginDefinition();
    $plugin->setProfile($profile);
    $plugin->createContent();
    $count = $plugin->count();

    if ($count !== FALSE) {
      drush_log("{$count} {$definition['label']}(s) created", LogLevel::INFO);
    }
  }
}

/**
 * Removes demo content.
 */
function drush_social_demo_demo_content_remove() {
  social_demo_drush_require();
  \Drupal::currentUser()->setAccount(User::load(1));
  $content_types = func_get_args();
  $profile = drush_get_option('profile', '');
  $manager = \Drupal::service('plugin.manager.demo_content');
  $plugins = $manager->createInstances($content_types);

  /** @var \Drupal\social_demo\DemoContentInterface $plugin */
  foreach ($plugins as $plugin) {
    $definition = $plugin->getPluginDefinition();
    $plugin->setProfile($profile);
    $plugin->removeContent();
    drush_log("{$definition['label']}(s) removed", LogLevel::INFO);
  }
}

/**
 * Add demo content.
 */
function drush_social_demo_add() {
  social_demo_drush_require();
  $content_types = func_get_args();
  _social_demo_convert_content_types($content_types);
  call_user_func_array('drush_social_demo_demo_content_add', $content_types);
}

/**
 * Remove demo content.
 */
function drush_social_demo_remove() {
  social_demo_drush_require();
  $content_types = func_get_args();
  _social_demo_convert_content_types($content_types);
  call_user_func_array('drush_social_demo_demo_content_remove', $content_types);
}

/**
 * Convert old keys of content types to new.
 *
 * @param array $content_types
 *   Array containing content types.
 */
function _social_demo_convert_content_types(array &$content_types = []) {
  $replacements = [
    'eventenrollment' => 'event_enrollment',
    'eventtype' => 'event_type',
    'likes' => 'like',
  ];

  foreach ($content_types as &$content_type) {
    if (isset($replacements[$content_type])) {
      $content_type = $replacements[$content_type];
    }
  }
}

/**
 * Creates demo content.
 */
function drush_social_demo_generate() {
  social_demo_drush_require();
  \Drupal::currentUser()->setAccount(User::load(1));
  $input_args = func_get_args();
  $content_types = [];
  foreach ($input_args as $input_arg) {
    $pieces = explode(':', $input_arg);
    $content_type = $pieces[0];
    $content_types[] = $content_type;
    $num_content_types[$content_type] = $pieces[1];
  }

  $manager = \Drupal::service('plugin.manager.demo_content');
  $plugins = $manager->createInstances($content_types);

  /** @var \Drupal\social_demo\DemoContentInterface $plugin */
  foreach ($plugins as $plugin) {
    $num = $num_content_types[$plugin->getPluginId()];
    $definition = $plugin->getPluginDefinition();
    $plugin->createContent(TRUE, $num);
    $count = $plugin->count();

    if ($count !== FALSE) {
      drush_log("{$count} {$definition['label']}(s) created", LogLevel::INFO);
    }
  }
}

/**
 * Load the Drush stub class.
 */
function social_demo_drush_require() {
  require_once DRUPAL_ROOT . '/profiles/contrib/social/modules/custom/social_demo/social_demo.drush_testing.inc';
}
