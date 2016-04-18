<?php

/*
 * @file
 * Helper functions for the fieldgroup module.
 */

use Drupal\Component\Utility\SafeMarkup;

/**
 * Get the default formatter settings for a given formatter and context.
 */
function _field_group_get_default_formatter_settings($format_type, $context) {
  $manager = \Drupal::service('plugin.manager.field_group.formatters');
  return $manager->getDefaultSettings($format_type, $context);
}
