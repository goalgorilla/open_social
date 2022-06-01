<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsGroupsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_groups_created",
 *  label = @Translation("Groups created"),
 *  weight = -200,
 * )
 */
class UserAnalyticsGroupsCreated extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Groups created');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $user_id = $entity->id();
    if (!is_int($user_id)) {
      return "0";
    }

    $query = $this->database->select('groups', 'g');
    $query->join('groups_field_data', 'gfd', 'gfd.id = g.id');
    $query->condition('gfd.uid', (string) $user_id);

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
