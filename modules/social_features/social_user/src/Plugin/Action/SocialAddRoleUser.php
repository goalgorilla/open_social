<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_user\UserRoleHelper;
use Drupal\user\Plugin\Action\ChangeUserRoleBase;
use Drupal\user\RoleInterface;

/**
 * Adds a role to a user but restricted by role.
 *
 * @Action(
 *   id = "social_user_add_role_action",
 *   label = @Translation("Add a role to the selected users"),
 *   type = "social_user"
 * )
 */
class SocialAddRoleUser extends ChangeUserRoleBase {

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

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\social_user\UserRoleHelper $roles */
    $roles = UserRoleHelper::alterGrantRevokeRoleOptions(user_role_names(TRUE), 'sitemanager', ['administrator']);
    // Remove the authenticated role.
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $form['rid'] = [
      '#type' => 'radios',
      '#title' => t('Role'),
      '#options' => $roles,
      '#default_value' => $this->configuration['rid'],
      '#required' => TRUE,
    ];
    return $form;
  }

}
