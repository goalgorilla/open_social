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
    $query = $this->database->select('event_enrollment', 'ee');
    $query->join('event_enrollment_field_data', 'eefd', 'eefd.id = ee.id');
    $query->condition('eefd.user_id', $entity->id());

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
