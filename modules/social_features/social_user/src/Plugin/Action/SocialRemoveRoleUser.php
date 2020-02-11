<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\user\Plugin\Action\ChangeUserRoleBase;

/**
 * Removes a role from a user.
 *
 * @Action(
 *   id = "social_user_remove_role_action",
 *   label = @Translation("Remove a role from the selected users"),
 *   type = "user"
 * )
 */
class SocialRemoveRoleUser extends ChangeUserRoleBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    $rid = $this->configuration['rid'];
    // Skip adding the role to the user if they already have it.
    if ($account !== FALSE && !$account->hasRole($rid)) {
      // For efficiency manually save the original account before applying
      // any changes.
      $account->original = clone $account;
      $account->addRole($rid);
      $account->save();
    }
  }

}
