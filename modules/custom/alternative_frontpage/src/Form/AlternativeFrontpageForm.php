<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\Role;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->typedConfigManager = $container->get('config.typed');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pathValidator = $container->get('path.validator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $settings = $this->entity;

    $ignored_roles = ['administrator'];
    $options = [];

    foreach (Role::loadMultiple() as $role) {
      if (in_array($role->id(), $ignored_roles, TRUE)) {
        continue;
      }
      $options[$role->id()] = $role->label();
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
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
      elseif (strpos($path, '/') !== 0) {
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
   * @param int|string $id
   *   Entity ID to check.
   *
   * @return bool
   *   Returns true when the configuration exists.
   */
  public function exist($id): bool {
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
   * @param int|string $id
   *   Entity ID to check.
   *
   * @return bool
   *   Returns true when the configuration exists.
   */
  public function roleExist(string $roles_target_id, $id): bool {
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
  private function isAllowedPath(string $path): bool {
    $unallowed_paths = [
      '/user/logout',
      '/ajax',
    ];
    foreach ($unallowed_paths as $unallowed_path) {
      if (strpos($path, $unallowed_path) === 0) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
