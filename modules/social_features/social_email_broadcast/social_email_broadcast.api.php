<?php

/**
 * @file
 * Hooks provided by the Social Email Broadcast module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter notification settings display.
 *
 * @param array &$items
 *   An array of groups that contain a title, label and entity types metadata.
 *
 * @see social_email_broadcast_form_user_form_alter()
 */
function hook_social_email_broadcast_notifications_alter(array &$items): void {
  $items['community_updates']['bulk_mailing'][] = [
    'name' => 'custom_entity_type_bulk_mailing',
    'label' => t('Unsubscribe from emails on "custom_entity_type" updates'),
    'entity_type' => [
      'custom_entity_type' => ['first_bundle', 'second_bundle'],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
