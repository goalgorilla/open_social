<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContext\ProfileActivityContext.
 */

namespace Drupal\activity_creator\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'ProfileActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "profile_activity_context",
 *  label = @Translation("Profile activity context"),
 * )
 */
class ProfileActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // Only return the referenced entity here.
    // @TODO Is referenced entity always the profile owner
    if ($data['entity_type'] && $data['entity_id']) {
      $recipients[] = [
        'id' => $data['entity_id'],
        'type' => $data['entity_type'],
      ];
    }

    return [];
  }

}
