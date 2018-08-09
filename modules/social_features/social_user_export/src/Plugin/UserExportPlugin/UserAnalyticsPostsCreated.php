<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsPostsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_posts_created",
 *  label = @Translation("Posts created"),
 *  weight = -260,
 * )
 */
class UserAnalyticsPostsCreated extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Posts created');
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
    $query = $this->database->select('post', 'p');
    $query->join('post_field_data', 'pfd', 'pfd.id = p.id');
    $query->condition('pfd.user_id', $entity->id());

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
