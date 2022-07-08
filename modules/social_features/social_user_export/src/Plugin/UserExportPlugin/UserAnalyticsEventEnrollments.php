<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsEventEnrollments' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_event_enrollments",
 *  label = @Translation("Event enrollments"),
 *  weight = -210,
 * )
 */
class UserAnalyticsEventEnrollments extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Event enrollments');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $user_id = $entity->id();
    if (!is_int($user_id)) {
      return "0";
    }

    $query = $this->database->select('event_enrollment', 'ee');
    $query->join('event_enrollment_field_data', 'eefd', 'eefd.id = ee.id');
    $query->condition('eefd.user_id', (string) $user_id);

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
