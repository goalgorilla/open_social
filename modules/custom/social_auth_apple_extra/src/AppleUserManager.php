<?php

namespace Drupal\social_auth_apple_extra;

use Drupal\social_auth_extra\UserManager;

/**
 * Class AppleUserManager.
 *
 * @package Drupal\social_auth_apple_extra
 */
class AppleUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return 'apple';
  }

}
