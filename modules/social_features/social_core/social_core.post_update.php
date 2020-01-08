<?php

/**
 * @file
 * Contains post-update hooks for the Social Core module.
 */

/**
 * Enable the queue storage entity module.
 */
function social_core_post_update_8701_enable_queue_storage() {
  \Drupal::service('module_installer')->install([
    'social_queue_storage',
  ]);
}
