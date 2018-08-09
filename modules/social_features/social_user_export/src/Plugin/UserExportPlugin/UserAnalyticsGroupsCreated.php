<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsGroupsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_groups_created",
 *  label = @Translation("Groups created"),
 *  weight = -210,
 * )
 */
class UserAnalyticsGroupsCreated extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Groups created');
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
    $query = $this->database->select('groups', 'g');
    $query->join('groups_field_data', 'gfd', 'gfd.id = g.id');
    $query->condition('gfd.uid', $entity->id());

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
