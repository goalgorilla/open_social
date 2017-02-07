<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_auth_extra\UserManager;
use Drupal\social_auth_facebook\Settings\FacebookAuthSettings;

/**
 * Class FacebookUserManager
 * @package Drupal\social_auth_facebook
 */
class FacebookUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return FacebookAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($account_id) {
    $this->account->get('facebook_id')->setValue($account_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->account->get('facebook_id')->value;
  }

}
