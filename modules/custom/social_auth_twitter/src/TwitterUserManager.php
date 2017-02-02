<?php

namespace Drupal\social_auth_twitter;

use Drupal\social_sso\UserManager;
use Drupal\social_auth_twitter\Settings\TwitterAuthSettings;

/**
 * Class TwitterUserManager
 * @package Drupal\social_auth_twitter
 */
class TwitterUserManager extends UserManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return TwitterAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($account_id) {
    $this->account->get('twitter_id')->setValue($account_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->account->get('twitter_id')->value;
  }

}
