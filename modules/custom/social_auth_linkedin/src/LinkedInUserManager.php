<?php

namespace Drupal\social_auth_linkedin;

use Drupal\social_auth_extra\UserManager;

/**
 * Class LinkedInUserManager.
 *
 * @package Drupal\social_auth_linkedin
 */
class LinkedInUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return 'linkedin';
  }

}
