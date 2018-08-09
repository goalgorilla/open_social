<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserSkills' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_skills",
 *  label = @Translation("Skills"),
 *  weight = -300,
 * )
 */
class UserSkills extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Skills');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetTaxonomyFieldValue('field_profile_expertise', $this->getProfile($entity));
  }

}
