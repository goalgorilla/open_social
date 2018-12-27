<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPlugin\UserRegistration;
use Drupal\user\UserInterface;

/**
 * Provides a 'EnrolmentUserRegistration' user export row.
 *
 * @UserExportPlugin(
 *  id = "enrolment_user_registration",
 *  label = @Translation("Registration date"),
 *  weight = -410,
 * )
 */
class EnrolmentUserRegistration extends UserRegistration {

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($entity->isAnonymous()) {
      $entity = &$this->configuration['entity'];
    }

    return $this->format($entity);
  }

}
