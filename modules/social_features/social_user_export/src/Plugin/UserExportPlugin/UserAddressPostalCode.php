<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
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

  /**
   * {@inheritdoc}
   */
  public function getHeader(): string {
    return $this->t('Postal code');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $this->profileGetAddressFieldValue('field_profile_address', 'postal_code', $this->getProfile($entity));
  }

}
