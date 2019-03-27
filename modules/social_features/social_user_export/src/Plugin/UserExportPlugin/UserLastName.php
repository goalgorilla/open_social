<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserLastName' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_last_name",
 *  label = @Translation("Last name"),
 *  weight = -470,
 * )
 */
class UserLastName extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Last name');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->profileGetFieldValue('field_profile_last_name', $this->getProfile($entity));
  }

}
