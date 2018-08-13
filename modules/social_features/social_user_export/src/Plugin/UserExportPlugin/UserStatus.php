<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserStatus' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_status",
 *  label = @Translation("Status"),
 *  weight = -400,
 * )
 */
class UserStatus extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Status');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return $entity->isActive() ? $this->t('Active') : $this->t('Blocked');
  }

}
