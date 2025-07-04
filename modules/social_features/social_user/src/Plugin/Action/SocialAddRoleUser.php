<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\role_delegation\DelegatableRolesInterface;
use Drupal\user\Plugin\Action\ChangeUserRoleBase;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a role to a user but restricted by role.
 */
#[Action(
  id: 'social_user_add_role_action',
  label: new TranslatableMarkup('Add a role to the selected users'),
  type: 'social_user',
)]
class SocialAddRoleUser extends ChangeUserRoleBase {

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
