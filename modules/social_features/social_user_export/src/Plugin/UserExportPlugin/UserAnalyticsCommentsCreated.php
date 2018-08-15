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
    $query = $this->database->select('comment', 'c');
    $query->join('comment_field_data', 'cfd', 'cfd.cid = c.cid');
    $query->condition('cfd.uid', $entity->id());

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
