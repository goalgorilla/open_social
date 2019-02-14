<?php

/**
 * @file
 * Post update hooks for this module.
 */

/**
 * Update message template settings based on configuration schema metadata.
 */
function activity_logger_post_update_apply_schema_changes() {
  /** @var \Drupal\message\MessageTemplateInterface[] $message_templates */
  $message_templates = \Drupal::entityTypeManager()->getStorage('message_template')->loadMultiple();
  // Resaving all the messages should be enough, the schema was built to fit
  // the messages.
  foreach ($message_templates as $message_template) {
    $message_template->save();
  }
}
