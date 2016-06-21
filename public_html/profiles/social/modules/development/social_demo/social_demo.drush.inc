<?php

/**
 * @file
 * Contains social_demo.drush.inc.
 */

/**
 *
 */
function social_demo_drush_command() {
  $items['social-demo-add'] = array(
    'description' => dt('Add demo content.'),
    'arguments' => array(
      'content_types' => dt('A space-separated list of content types.'),
    ),
    'examples' => array(
      "drush social-demo-add user topic" => "Generates demo content for users and topics.",
    ),
    'required-arguments' => 1,
    'aliases' => array('sda'),
  );
  $items['social-demo-remove'] = array(
    'description' => dt('Remove demo content.'),
    'arguments' => array(
      'content_types' => dt('A space-separated list of content types.'),
    ),
    'examples' => array(
      "drush social-demo-remove user topic" => "Removes demo content for users and topics.",
    ),
    'required-arguments' => 1,
    'aliases' => array('sdr'),
  );
  return $items;
}

/**
 * Add demo content.
 */
function drush_social_demo_add() {
  $content_types = func_get_args();

  foreach ($content_types as $key => $type) {
    $num_created = 0;
    // Create an instance of the necessary class.
    $className = "\Drupal\social_demo\Content\SocialDemo" . ucfirst($type);

    if (class_exists($className)) {
      $container = \Drupal::getContainer();
      $class = $className::create($container);
      $num_created = $class->createContent();
    }

    drush_print($num_created . " " . $type . " created");
  }

}

/**
 * Remove demo content.
 */
function drush_social_demo_remove() {
  $content_types = func_get_args();

  foreach ($content_types as $key => $type) {
    // Create an instance of the necessary class.
    $className = "\Drupal\social_demo\Content\SocialDemo" . ucfirst($type);

    if (class_exists($className)) {
      $container = \Drupal::getContainer();
      $class = $className::create($container);
      $class->removeContent();
    }
  }

}
