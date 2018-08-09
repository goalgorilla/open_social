<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsCommentsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_comments_created",
 *  label = @Translation("Comments created"),
 *  weight = -250,
 * )
 */
class UserAnalyticsCommentsCreated extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Comments created');
  }

  /**
   * Returns the value.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The User entity to get the value from.
   *
   * @return string
   *   The value.
   */
  public function getValue(UserInterface $entity) {
    $query = $this->database->select('comment', 'c');
    $query->join('comment_field_data', 'cfd', 'cfd.cid = c.cid');
    $query->condition('cfd.uid', $entity->id());

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
