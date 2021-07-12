<?php

/**
 * @file
 * Defines stubs for Drush functions and constants so tests can run.
 *
 * @see \Drupal\social_demo\drush.inc
 */

if (!function_exists('drush_print')) {

  /**
   * Stub for drush_print() function for testing.
   */
  function drush_print() {
  }

}

if (!function_exists('dt')) {

  /**
   * Stub for dt() function for testing.
   */
  function dt($text) {
    return $text;
  }

}

if (!function_exists('drush_log')) {

  /**
   * Stub for drush_log() function for testing.
   */
  function drush_log($text, $level) {
    return \Drupal::logger('social_book')->info($text);
  }

}

if (!function_exists('drush_get_option')) {

  /**
   * Stub for drush_log() function for testing.
   */
  function drush_get_option($profile, $default) {
    return $default;
  }

}
