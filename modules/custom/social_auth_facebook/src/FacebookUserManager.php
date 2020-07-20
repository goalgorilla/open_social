<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_auth_extra\UserManager;

/**
 * Class FacebookUserManager.
 *
 * @package Drupal\social_auth_facebook
 */
class FacebookUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return 'facebook';
  }

}
