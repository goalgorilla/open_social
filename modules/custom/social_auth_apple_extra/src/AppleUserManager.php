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

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    $entities = $this->entityTypeManager->getStorage('social_auth')->loadByProperties([
      'user_id' => $this->account->id(),
    ]);

    if ($entities) {
      return reset($entities)->provider_user_id->value;
    }

    return parent::getAccountId();
  }

}
