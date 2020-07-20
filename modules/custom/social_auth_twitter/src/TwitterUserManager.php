<?php

namespace Drupal\social_auth_twitter;

use Drupal\social_auth_extra\UserManager;

/**
 * Class TwitterUserManager.
 *
 * @package Drupal\social_auth_twitter
 */
class TwitterUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return 'twitter';
  }

}
