<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return $this->t('Last access');
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
    if ($last_access_time = $entity->getLastAccessedTime()) {
      $last_access = $this->dateFormatter->format($last_access_time, 'custom', 'Y/m/d - H:i');
    }
    else {
      $last_access = t('never');
    }
    return $last_access;
  }

}
