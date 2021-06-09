<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\role_delegation\DelegatableRolesInterface;
use Drupal\user\Plugin\Action\ChangeUserRoleBase;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes a role from a user but restricted by role.
 *
 * @Action(
 *   id = "social_user_remove_role_action",
 *   label = @Translation("Remove a role from the selected users"),
 *   type = "social_user"
 * )
 */
class SocialRemoveRoleUser extends ChangeUserRoleBase implements ContainerFactoryPluginInterface {

  /**
   * The account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The role delegation delegatable roles interface.
   *
   * @var \Drupal\role_delegation\DelegatableRolesInterface
   */
  protected $delegatableRoles;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type, AccountProxyInterface $currentUser, DelegatableRolesInterface $delegatableRoles) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type);
    $this->entityType = $entity_type;
    $this->currentUser = $currentUser->getAccount();
    $this->delegatableRoles = $delegatableRoles;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')->getDefinition('user_role'),
      $container->get('current_user'),
      $container->get('delegatable_roles')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    $rid = $this->configuration['rid'];
    // Skip removing the role from the user if they already don't have it.
    if ($account !== FALSE && $account->hasRole($rid)) {
      // For efficiency manually save the original account before applying
      // any changes.
      $account->original = clone $account;
      $account->removeRole($rid);
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roles = $this->delegatableRoles->getAssignableRoles($this->currentUser);
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
