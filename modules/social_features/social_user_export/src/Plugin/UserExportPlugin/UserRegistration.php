<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserRegistration' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_registration",
 *  label = @Translation("Registration date"),
 *  weight = -410,
 * )
 */
class UserRegistration extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Registration date');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $this->dateFormatter->format($entity->getCreatedTime(), 'short');
  }

}
