<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
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
  public function getHeader(): TranslatableMarkup {
    return $this->t('Groups created');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    $query = $this->database->select('groups', 'g');
    $query->join('groups_field_data', 'gfd', 'gfd.id = g.id');
    $query->condition('gfd.uid', $entity->id());

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
