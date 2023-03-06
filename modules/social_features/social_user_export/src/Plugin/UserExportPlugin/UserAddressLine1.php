<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAddressLine1' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_address_line1",
 *  label = @Translation("Address line 1"),
 *  weight = -350,
 *  dependencies = @PluginDependency(
 *    config = {
 *      "field.field.profile.profile.field_profile_address",
 *    },
 *  )
 * )
 */
class UserAddressLine1 extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Address line 1');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetAddressFieldValue('field_profile_address', 'address_line1', $this->getProfile($entity));
  }

}
