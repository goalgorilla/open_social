<?php

namespace Drupal\social_auth_google;

use Drupal\social_auth_extra\UserManager;
use Drupal\social_auth_google\Settings\GoogleAuthSettings;

/**
 * Class GoogleUserManager
 * @package Drupal\social_auth_google
 */
class GoogleUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return GoogleAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($account_id) {
    $this->account->get('google_id')->setValue($account_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->account->get('google_id')->value;
  }

}
