<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsEventEnrollments' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_event_enrollments",
 *  label = @Translation("Event enrollments"),
 *  weight = -230,
 * )
 */
class UserAnalyticsEventEnrollments extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Event enrollments');
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
    $query = $this->database->select('event_enrollment', 'ee');
    $query->join('event_enrollment_field_data', 'eefd', 'eefd.id = ee.id');
    $query->condition('eefd.user_id', $entity->id());

    return (string) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
