<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsEventsCreated' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_events_created",
 *  label = @Translation("Events created"),
 *  weight = -220,
 * )
 */
class UserAnalyticsEventsCreated extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Events created');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $query = $this->database->select('node', 'n');
    $query->join('node_field_data', 'nfd', 'nfd.nid = n.nid');
    $query
      ->condition('nfd.uid', $entity->id())
      ->condition('nfd.type', 'event');

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
