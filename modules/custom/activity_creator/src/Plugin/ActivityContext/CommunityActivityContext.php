<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContext\CommunityActivityContext.
 */

namespace Drupal\activity_creator\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'CommunityActivityContext' acitivy context.
 *
 * @ActivityContext(
 *  id = "community_activity_context",
 *  label = @Translation("Community activity context"),
 * )
 */
class CommunityActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    // Always return empty array here. Since community does not have specific
    // recipients.
    return [];
  }

}
