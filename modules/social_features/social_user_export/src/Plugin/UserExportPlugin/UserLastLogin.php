<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Last login');
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
    if ($last_login_time = $entity->getLastLoginTime()) {
      $last_login = $this->dateFormatter->format($last_login_time, 'custom', 'Y/m/d - H:i');
    }
    else {
      $last_login = t('never');
    }
    return $last_login;
  }

}
