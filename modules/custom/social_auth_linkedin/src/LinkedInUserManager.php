<?php

namespace Drupal\social_auth_linkedin;

use Drupal\social_auth_extra\UserManager;
use Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings;

/**
 * Class LinkedInUserManager
 * @package Drupal\social_auth_linkedin
 */
class LinkedInUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return LinkedInAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($account_id) {
    $this->account->get('linkedin_id')->setValue($account_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->account->get('linkedin_id')->value;
  }

}
