<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressPostalCode' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_postal_code",
 *  label = @Translation("Postal code"),
 *  weight = -360,
 * )
 */
class UserAddressPostalCode extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Postal code');
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
    return $this->profileGetAddressFieldValue('field_profile_address', 'postal_code', $profile);
  }

}
