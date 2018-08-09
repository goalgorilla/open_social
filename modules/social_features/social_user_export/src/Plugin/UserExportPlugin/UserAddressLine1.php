<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressLine1' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_line1",
 *  label = @Translation("Address line 1"),
 *  weight = -350,
 * )
 */
class UserAddressLine1 extends UserExportPluginBase {

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Address line 1');
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
    return $this->profileGetAddressFieldValue('field_profile_address', 'address_line1', $profile);
  }

}
