<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserOrganization' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_organization",
 *  label = @Translation("Primary affiliation"),
 *  weight = -320,
 * )
 */
class UserOrganization extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Primary affiliation');
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
    $profile = $this->getProfile($entity);

    return $profile instanceof ProfileAffiliationInterface
      ? $profile->getPrimaryAffiliationName()
      : '';
  }

}
