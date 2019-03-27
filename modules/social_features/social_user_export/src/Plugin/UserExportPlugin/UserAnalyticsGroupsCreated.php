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
    $query = $this->database->select('groups', 'g');
    $query->join('groups_field_data', 'gfd', 'gfd.id = g.id');
    $query->condition('gfd.uid', $entity->id());

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
