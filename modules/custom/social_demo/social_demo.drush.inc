<?php

/**
 * @file
 * Contains social_demo.drush.inc.
 */

use Drush\Log\LogLevel;
use Drupal\user\Entity\User;

/**
 * Implements hook_drush_command().
 */
function social_demo_drush_command() {
  $items['demo-content-add'] = [
    'description' => dt('Create demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'options' => [
      'module' => dt('The module name that provides content data.'),
    ],
    'required-arguments' => TRUE,
  ];

  $items['demo-content-remove'] = [
    'description' => dt('Remove demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'options' => [
      'module' => dt('The module name that provides content data.'),
    ],
    'required-arguments' => TRUE,
  ];

  $items['social-demo-add'] = [
    'description' => dt('Add demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'examples' => [
      'drush social-demo-add user topic' => dt('Generates demo content for users and topics.'),
    ],
    'required-arguments' => 1,
    'aliases' => ['sda'],
  ];

  $items['social-demo-remove'] = [
    'description' => dt('Remove demo content.'),
    'arguments' => [
      'content_types' => dt('A space-separated list of content types.'),
    ],
    'examples' => [
      'drush social-demo-remove user topic' => dt('Removes demo content for users and topics.'),
    ],
    'required-arguments' => 1,
    'aliases' => ['sdr'],
  ];

  return $items;
}

/**
 * Creates demo content.
 */
function drush_social_demo_demo_content_add() {
  \Drupal::currentUser()->setAccount(User::load(1));
  $content_types = func_get_args();
  $manager = \Drupal::service('plugin.manager.demo_content');
  $plugins = $manager->createInstances($content_types);

  /** @var \Drupal\social_demo\DemoContentInterface $plugin */
  foreach ($plugins as $plugin) {
    $definition = $plugin->getPluginDefinition();
    $plugin->createContent();
    $count = $plugin->count();

    if ($count !== FALSE) {
      drush_log("{$count} {$definition['label']}(s) created", LogLevel::SUCCESS);
    }
  }
}

/**
 * Removes demo content.
 */
function drush_social_demo_demo_content_remove() {
  \Drupal::currentUser()->setAccount(User::load(1));
  $content_types = func_get_args();
  $manager = \Drupal::service('plugin.manager.demo_content');
  $plugins = $manager->createInstances($content_types);

  /** @var \Drupal\social_demo\DemoContentInterface $plugin */
  foreach ($plugins as $plugin) {
    $definition = $plugin->getPluginDefinition();
    $plugin->removeContent();
    drush_log("{$definition['label']}(s) removed", LogLevel::SUCCESS);
  }
}

/**
 * Add demo content.
 */
function drush_social_demo_add() {
  $content_types = func_get_args();
  _social_demo_convert_content_types($content_types);
  call_user_func_array('drush_social_demo_demo_content_add', $content_types);
}

/**
 * Remove demo content.
 */
function drush_social_demo_remove() {
  $content_types = func_get_args();
  _social_demo_convert_content_types($content_types);
  call_user_func_array('drush_social_demo_demo_content_remove', $content_types);
}

/**
 * Convert old keys of content types to new.
 *
 * @param array $content_types
 */
function _social_demo_convert_content_types(&$content_types = []) {
  $replacements = [
    'eventenrollment' => 'event_enrollment',
    'eventtype' => 'event_type',
    'likes' => 'like',
  ];

  foreach ($content_types as &$content_type) {
    if (isset($replacements[ $content_type ])) {
      $content_type = $replacements[ $content_type ];
    }
  }
}
