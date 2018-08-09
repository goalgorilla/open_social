<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserInterests' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_interests",
 *  label = @Translation("Interests"),
 *  weight = -290,
 * )
 */
class UserInterests extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Interests');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetTaxonomyFieldValue('field_profile_interests', $this->getProfile($entity));
  }

}
