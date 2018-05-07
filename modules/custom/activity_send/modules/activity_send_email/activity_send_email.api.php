<?php

/**
 * @file
 * Hooks provided by the Activity Send Email module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter notification settings display.
 *
 * @param array &$items
 *   An array of groups that contain a title and an array of templates that are
 *   contained in this settings group.
 * @param array $email_message_templates
 *   Message templates enabled for sending by email.
 *
 * @see activity_send_email_form_user_form_alter()
 */
function hook_activity_send_email_notifications_alter(array &$items, array $email_message_templates) {
  // If a create_private_message template is enabled then we add it in the
  // "Message to Me" section.
  if (isset($email_message_templates['create_private_message'])) {
    $items['message_to_me']['templates'][] = 'create_private_message';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
