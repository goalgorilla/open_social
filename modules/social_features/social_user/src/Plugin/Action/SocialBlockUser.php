<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

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
  public function execute(?UserInterface $account = NULL): void {
    if ($account === NULL) {
      return;
    }

    // Skip blocking user if they are already blocked.
    if ($account->isActive()) {
      // For efficiency manually save the original account before applying any
      // changes.
      $original_account = clone $account;
      $account->block();
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE): bool|int|AccessResultInterface {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->get('status')->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed() | $account?->hasPermission('block users');
  }

}
