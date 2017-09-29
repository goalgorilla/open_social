<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Alternate action plugin that can block users.
 *
 * Adds new checking based on permissions.
 *
 * @see \Drupal\user\Plugin\Action\BlockUser
 */
class SocialBlockUser extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Skip blocking user if they are already blocked.
    if ($account !== FALSE && $account->isActive()) {
      // For efficiency manually save the original account before applying any
      // changes.
      $account->original = clone $account;
      $account->block();
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed() | AccessResult::allowedIf($account->hasPermission('block users'));
  }

}
