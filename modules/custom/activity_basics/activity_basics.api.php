<?php

/**
 * @file
 * Hooks provided by the Activity module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the recipients used as Organizers.
 *
 * @param array $recipients
 *   The recipients receiving a notification.
 * @param \Drupal\node\Entity\Node $event
 *   The Event that was joined
 *
 * @ingroup social_group_api
 */
function hook_activity_recipient_organizer_alter(&$recipients, $event) {
  $organizers = $event->getOwnerId();

  // Add the creator of the Event as a recipient.
  $recipients[] = [
    'target_type' => 'user',
    'target_id' => $organizers->getOwnerId(),
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
