<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsPostsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_posts_created",
 *  label = @Translation("Posts created"),
 *  weight = -250,
 * )
 */
class UserAnalyticsPostsCreated extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Posts created');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $query = $this->database->select('post', 'p');
    $query->join('post_field_data', 'pfd', 'pfd.id = p.id');
    $query->condition('pfd.user_id', $entity->id());

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
