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
