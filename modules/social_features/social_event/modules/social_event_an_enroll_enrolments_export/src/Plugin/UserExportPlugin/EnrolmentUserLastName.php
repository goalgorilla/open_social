<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPlugin\UserLastName;
use Drupal\user\UserInterface;

/**
 * Provides a 'EnrolmentUserLastName' user export row.
 *
 * @UserExportPlugin(
 *  id = "enrolment_user_last_name",
 *  label = @Translation("Last name"),
 *  weight = -470,
 * )
 */
class EnrolmentUserLastName extends UserLastName {

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($entity->isAnonymous()) {
      $entity = &$this->configuration['entity'];

      if (!$entity->field_last_name->isEmpty()) {
        return $entity->field_last_name->value;
      }

      return '';
    }

    return parent::getValue($entity);
  }

}
