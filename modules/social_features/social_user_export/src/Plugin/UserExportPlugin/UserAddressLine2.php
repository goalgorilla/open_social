<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressLine2' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_line2",
 *  label = @Translation("Address line 2"),
 *  weight = -340,
 * )
 */
class UserAddressLine2 extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Address line 2');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetAddressFieldValue('field_profile_address', 'address_line2', $this->getProfile($entity));
  }

}
