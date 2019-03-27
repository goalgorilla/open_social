<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserLastAccess' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_last_access",
 *  label = @Translation("Last access"),
 *  weight = -420,
 * )
 */
class UserLastAccess extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Last access');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($last_access_time = $entity->getLastAccessedTime()) {
      $last_access = $this->dateFormatter->format($last_access_time, 'short');
    }
    else {
      $last_access = t('never');
    }
    return $last_access;
  }

}
