<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressAdministrative' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_administrative",
 *  label = @Translation("Administrative address"),
 *  weight = -380,
 * )
 */
class UserAddressAdministrative extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Administrative address');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetAddressFieldValue('field_profile_address', 'administrative_area', $this->getProfile($entity));
  }

}
