<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressLocality' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_locality",
 *  label = @Translation("Address locality"),
 *  weight = -370,
 * )
 */
class UserAddressLocality extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Address locality');
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
    return $this->profileGetAddressFieldValue('field_profile_address', 'locality', $profile);
  }

}
