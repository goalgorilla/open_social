<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContext\GroupActivityContext.
 */

namespace Drupal\activity_creator\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'GroupActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "group_activity_context",
 *  label = @Translation("Group activity context"),
 * )
 */
class GroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // @TODO Retrieve the group members.
    
    // @TODO Is referenced entity always the Group owner
    if ($data['entity_type'] && $data['entity_id']) {
      $recipients[] = [
        'id' => $data['entity_id'],
        'type' => $data['entity_type'],
      ];
    }

    return $recipients;
  }

}
