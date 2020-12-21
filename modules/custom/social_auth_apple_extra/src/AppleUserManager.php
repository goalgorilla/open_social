<?php

namespace Drupal\social_auth_apple_extra;

use Drupal\social_auth_extra\UserManager;

/**
 * Defines the user manager service.
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
  public function setAccountId($account_id) {
    if ($account_id === NULL) {
      foreach ($this->getEntities() as $entity) {
        $entity->delete();
      }
    }
    else {
      parent::setAccountId($account_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    if ($entities = $this->getEntities()) {
      return reset($entities)->provider_user_id->value;
    }

    return parent::getAccountId();
  }

  /**
   * Returns list of the auth entities for a selected user.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities list.
   */
  protected function getEntities() {
    return $this->entityTypeManager->getStorage('social_auth')->loadByProperties([
      'user_id' => $this->account->id(),
      'plugin_id' => 'social_auth_apple',
    ]);
  }

}
