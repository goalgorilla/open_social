<?php

/**
 * @file
 * Hooks provided by the Activity module.
 */

use Drupal\node\Entity\Node;

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
 *   The Event that was joined.
 * @param array $data
 *   The data concerning the activity needed for context.
 *
 * @ingroup activity_basics_api
 */
function hook_activity_recipient_organizer_alter(array &$recipients, Node $event, array $data) {
  $organizers = $event->getOwnerId();

  if ($data['target_type'] !== 'event_enrollment') {
    return;
  }

  // Add the creator of the Event as a recipient.
  $recipients[] = [
    'target_type' => 'user',
    'target_id' => $organizers->getOwnerId(),
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
