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
    $user_id = $entity->id();
    if (!is_int($user_id)) {
      return "0";
    }

    $query = $this->database->select('node', 'n');
    $query->join('node_field_data', 'nfd', 'nfd.nid = n.nid');
    $query
      ->condition('nfd.uid', (string) $user_id)
      ->condition('nfd.type', 'event');

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
