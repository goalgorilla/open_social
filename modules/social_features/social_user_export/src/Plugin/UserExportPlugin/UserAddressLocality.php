<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
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

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Address locality');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $this->profileGetAddressFieldValue('field_profile_address', 'locality', $this->getProfile($entity));
  }

}
