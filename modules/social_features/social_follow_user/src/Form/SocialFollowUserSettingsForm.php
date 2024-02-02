<?php

namespace Drupal\social_follow_user\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure Social Follow User settings.
 *
 * @package Drupal\social_follow_user\Form
 */
class SocialFollowUserSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a SocialFollowUserSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entityTypeManager,
    ?TypedConfigManagerInterface $typedConfigManager = NULL,
  ) {
    parent::__construct($config_factory, $typedConfigManager);

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['social_follow_user.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_follow_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->config('social_follow_user.settings')->get('status'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $follow_status = $this->config('social_follow_user.settings')->get('status');
    $roles = $this->config('social_follow_user.settings')->get('roles');
    $permission = 'flag follow_user';

    // Remove the permissions for following users if disabled.
    if ($follow_status === TRUE && $form_state->getValue('status') === 0) {
      /** @var \Drupal\user\RoleStorageInterface $role_storage */
      $role_storage = $this->entityTypeManager->getStorage('user_role');

      // Permission can be different from default so retrieve it dynamically.
      $entity_roles = array_filter($role_storage->loadMultiple(), fn(RoleInterface $role) => $role->hasPermission($permission));
      $roles = array_map(fn(RoleInterface $role) => $role->id(), $entity_roles);

      foreach ($roles as $role) {
        user_role_revoke_permissions($role, [$permission]);
      }
    }
    elseif ($follow_status === FALSE && $form_state->getValue('status') === 1) {
      // If the config is not set yet, then it means we have the default.
      if ($roles !== NULL) {
        // Add the permission to follow users if the feature is turned on.
        foreach ($roles as $role) {
          user_role_grant_permissions($role, [$permission]);
        }
      }
    }

    $this->config('social_follow_user.settings')
      ->set('status', $form_state->getValue('status'))
      ->set('roles', $roles)
      ->save();
  }

}
