<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsLikes' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_likes",
 *  label = @Translation("Number of Likes"),
 *  weight = -190,
 * )
 */
class UserAnalyticsLikes extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Number of Likes');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $query = $this->database->select('votingapi_vote', 'v');
    $query->condition('v.user_id', $entity->id());

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
