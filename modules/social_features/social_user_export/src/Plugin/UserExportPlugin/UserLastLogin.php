<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserLastLogin' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_last_login",
 *  label = @Translation("Last login"),
 *  weight = -430,
 * )
 */
class UserLastLogin extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Last login');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    if ($last_login_time = $entity->getLastLoginTime()) {
      $last_login = $this->dateFormatter->format($last_login_time, 'short');
    }
    else {
      $last_login = t('never');
    }
    return $last_login;
  }

}
