<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserPhoneNumber' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_phone_number",
 *  label = @Translation("Phone number"),
 *  weight = -330,
 * )
 */
class UserPhoneNumber extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Phone number');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetFieldValue('field_profile_phone_number', $this->getProfile($entity));
  }

}
