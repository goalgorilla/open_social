<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPlugin\UserEmail;
use Drupal\user\UserInterface;

/**
 * Provides a 'EnrolmentUserEmail' user export row.
 *
 * @UserExportPlugin(
 *  id = "enrolment_user_email",
 *  label = @Translation("Email"),
 *  weight = -440,
 * )
 */
class EnrolmentUserEmail extends UserEmail {

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($entity->isAnonymous()) {
      return $this->configuration['entity']->field_email->value;
    }

    return parent::getValue($entity);
  }

}
