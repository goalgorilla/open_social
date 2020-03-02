<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Path\PathValidatorInterface;

/**
 * Form handler for the AlternativeFrontpage add and edit forms.
 */
class AlternativeFrontpageForm extends EntityForm {

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs an AlternativeFrontpageForm object.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The factory for configuration objects.
   */
  public function __construct(TypedConfigManagerInterface $typed_config_manager, EntityTypeManagerInterface $entity_type_manager, PathValidatorInterface $path_validator) {
    $this->typedConfigManager = $typed_config_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $settings = $this->entity;

    // Get all roles.
    $roles = Role::loadMultiple();

    // Build the options.
    $options = [];
    // For now only allow Authenticated and Anonymous.
    // Todo:: Extend for more roles.
    $allowed_roles = ['anonymous', 'authenticated'];
    foreach ($roles as $role) {
      if (in_array($role->id(), $allowed_roles, TRUE)) {
        $options[$role->id()] = $role->label();
      }
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $settings->label(),
      '#description' => $this->t("Label for the Frontpage Setting."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $settings->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$settings->isNew(),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage path'),
      '#maxlength' => 255,
      '#default_value' => $settings->path,
      '#description' => $this->t("Enter the frontpage. This setting will override the homepage which is set in the Site Configuration form. Enter the path starting with a forward slash. Default: /stream."),
      '#required' => TRUE,
    ];

    $form['roles_target_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('User roles'),
      '#options' => $options,
      '#default_value' => $settings->roles_target_id,
      '#description' => $this->t('Which roles should it apply to'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $path = $form_state->getValue('path');
    $role = $form_state->getValue('roles_target_id');
    $id = $form_state->getValue('id');

    if ($this->roleExist($role, $id)) {
      $form_state->setErrorByName('roles_target_id', $this->t('There is already a setting created for this role.'));
    }

    if ($path) {
      if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage is not valid.'));
      }
      elseif (substr($path, 0, 1) !== '/') {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage should start with a forward slash.'));
      }
      elseif (!$this->isAllowedPath($path)) {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage is not allowed.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $settings = $this->entity;
    $status = $settings->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label settting.', [
        '%label' => $settings->label(),
      ]));
    }

    $form_state->setRedirect('entity.alternative_frontpage.collection');
  }

  /**
   * Check whether an Alternative Frontpage configuration entity exists.
   *
   * @param int $id
   *   Entity Id to check.
   *
   * @return bool
   *   Returns true when the configuration exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('alternative_frontpage')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Check if there is already a configuration entity with the same user role.
   *
   * @param string $roles_target_id
   *   Role target to check.
   * @param int $id
   *   Entity Id to check.
   *
   * @return bool
   *   Returns true when the configuration exists.
   */
  public function roleExist($roles_target_id, $id) {
    $entity = $this->entityTypeManager->getStorage('alternative_frontpage')->getQuery()
      ->condition('roles_target_id', $roles_target_id)
      ->condition('id', $id, '<>')
      ->execute();
    return (bool) $entity;
  }

  /**
   * Checks if a path is allowed.
   *
   * Some paths are not allowed, e.g. /user/logout or /ajax.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   Returns true when path is allowed.
   */
  private function isAllowedPath($path) {
    $unallowed_paths = [
      '/user/logout',
      '/ajax',
    ];
    foreach ($unallowed_paths as $unallowed_path) {
      if ($unallowed_path === substr($path, 0, strlen($unallowed_path))) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
