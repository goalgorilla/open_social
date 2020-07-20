<?php

namespace Drupal\social_auth_google;

use Drupal\social_auth_extra\UserManager;

/**
 * Class GoogleUserManager.
 *
 * @package Drupal\social_auth_google
 */
class GoogleUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return 'google';
  }

}
