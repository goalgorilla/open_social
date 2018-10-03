<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressCountryCode' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_country_code",
 *  label = @Translation("Country code"),
 *  weight = -390,
 * )
 */
class UserAddressCountryCode extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Country code');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetAddressFieldValue('field_profile_address', 'country_code', $this->getProfile($entity));
  }

}
