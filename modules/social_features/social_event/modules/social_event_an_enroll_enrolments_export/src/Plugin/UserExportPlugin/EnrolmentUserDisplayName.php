<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPlugin\UserDisplayName;
use Drupal\user\UserInterface;

/**
 * Provides a 'EnrolmentUserDisplayName' user export row.
 *
 * @UserExportPlugin(
 *  id = "enrolment_display_name",
 *  label = @Translation("Display name"),
 *  weight = -450,
 * )
 */
class EnrolmentUserDisplayName extends UserDisplayName {

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($entity->isAnonymous()) {
      return $this->t('Guest');
    }

    return parent::getValue($entity);
  }

}
