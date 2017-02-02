<?php

namespace Drupal\social_like;

use \Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class SocialLikePermission {

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL) {

    if($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account ?: \Drupal::currentUser(), 'view like widget');
    }
    return AccessResult::neutral();
  }

}