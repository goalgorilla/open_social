<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPlugin\UserFirstName;
use Drupal\user\UserInterface;

/**
 * Provides a 'EnrolmentUserFirstName' user export row.
 *
 * @UserExportPlugin(
 *  id = "enrolment_user_first_name",
 *  label = @Translation("First name"),
 *  weight = -480,
 * )
 */
class EnrolmentUserFirstName extends UserFirstName {

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($entity->isAnonymous()) {
      $entity = &$this->configuration['entity'];

      if (!$entity->field_first_name->isEmpty()) {
        return $entity->field_first_name->value;
      }

      return '';
    }

    return parent::getValue($entity);
  }

}
