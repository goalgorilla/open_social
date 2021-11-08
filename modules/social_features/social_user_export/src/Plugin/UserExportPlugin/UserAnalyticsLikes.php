<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
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
  public function getHeader(): string {
    return $this->t('Number of Likes');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    $query = $this->database->select('votingapi_vote', 'v');
    $query->condition('v.user_id', $entity->id());

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
