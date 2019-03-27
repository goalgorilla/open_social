<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserOrganization' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_organization",
 *  label = @Translation("Organization"),
 *  weight = -320,
 * )
 */
class UserOrganization extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Organization');
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
    return $this->profileGetFieldValue('field_profile_organization', $this->getProfile($entity));
  }

}
