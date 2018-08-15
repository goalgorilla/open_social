<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserProfileTag' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_profile_tag",
 *  label = @Translation("Profile tag"),
 *  weight = -270,
 * )
 */
class UserProfileTag extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Profile tag');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetTaxonomyFieldValue('field_profile_profile_tag', $this->getProfile($entity));
  }

}
