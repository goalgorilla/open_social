<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsCommentsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_comments_created",
 *  label = @Translation("Comments created"),
 *  weight = -240,
 * )
 */
class UserAnalyticsCommentsCreated extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Comments created');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $user_id = $entity->id();
    if (!is_int($user_id)) {
      return "0";
    }

    $query = $this->database->select('comment', 'c');
    $query->join('comment_field_data', 'cfd', 'cfd.cid = c.cid');
    $query->condition('cfd.uid', (string) $user_id);

    $result = $query
      ->countQuery()
      ->execute();
    if ($result === NULL) {
      return "0";
    }

    // Cast to int first so an empty result registers a "0". We cast to string
    // to satisfy the user export plugin interface.
    return (string) (int) $result->fetchField();
  }

}
